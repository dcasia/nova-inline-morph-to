<?php

namespace DigitalCreative\InlineMorphTo;

use Laravel\Nova\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Http\Controllers\CreationFieldController;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Laravel\Nova\Http\Controllers\ResourceShowController;
use Laravel\Nova\Http\Controllers\UpdateFieldController;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class InlineMorphTo extends Field
{

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'inline-morph-to';

    public $typeUpdateable = false;

    /**
     * Create a new field.
     *
     * @param string $name
     * @param string|callable|null $attribute
     * @param callable|null $resolveCallback
     *
     * @return void
     */
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->meta = [
            'resources' => [],
            'listable' => true
        ];
    }


    /**
     * Specify if a relation can be changed.
     *
     * @param bool $value
     * @return $this
     */
    public function typeUpdateable($value = true): InlineMorphTo
    {
        $this->typeUpdateable = $value;

        return $this;
    }

    /**
     * Format:
     *
     * [ SomeNovaResource1::class, SomeNovaResource2::class ]
     * [ 'Some Display Text 1' => SomeNovaResource2::class, 'Some Display Text 2' => SomeNovaResource2::class ]
     *
     * @param array $types
     *
     * @return $this
     */
    public function types(array $types): self
    {
        $types = collect($types)->map(function (string $resource, $key) {

            /**
             * @var Resource $resourceInstance
             */
            $resourceInstance = new $resource($resource::newModel());

            return [
                'className' => $resource,
                'uriKey' => $resource::uriKey(),
                'label' => is_numeric($key) ? $resource::label() : $key,
                'fields' => $this->resolveFields($resourceInstance)
            ];
        });

        $this->withMeta(['resources' => $types->values()]);

        return $this;
    }

    private function resolveFields(Resource $resourceInstance): Collection
    {

        /**
         * @var NovaRequest $request
         */
        $request = app(NovaRequest::class);
        $controller = $request->route()->controller;

        switch (get_class($controller)) {

            case CreationFieldController::class:
                return $resourceInstance->creationFields($request);

            case UpdateFieldController::class:
                return $resourceInstance->updateFields($request);

            case ResourceShowController::class:
                return $resourceInstance->detailFields($request);

            case ResourceIndexController::class:
                return $resourceInstance->indexFields($request);

        }

        return $resourceInstance->availableFields($request);
    }

    /**
     * Resolve the field's value for display.
     *
     * @param mixed $resource
     * @param string|null $attribute
     * @return void
     */
    public function resolveForDisplay($resource, $attribute = null)
    {

        /**
         * @var null|Model $relationInstance
         * @var Field $field
         */
        $attribute = $attribute ?? $this->attribute;

        parent::resolveForDisplay($resource, $attribute);

        if ($relationInstance = $resource->$attribute) {
            foreach ($this->getFields($relationInstance) as $field) {
                $field->resolveForDisplay($relationInstance);
            }
        }
    }

    /**
     * Resolve the field's value.
     *
     * @param mixed $resource
     * @param string|null $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {

        /**
         * @var null|Model $relationInstance
         * @var Field $field
         */
        $attribute = $attribute ?? $this->attribute;

        parent::resolve($resource, $attribute);

        if ($relationInstance = $resource->$attribute) {
            foreach ($this->getFields($relationInstance) as $field) {
                if ($field->computed()) {
                    $field->computedCallback = $field->computedCallback->bindTo(
                        Nova::newResourceFromModel($relationInstance)
                    );
                }

                $field->resolve($relationInstance);
            }
        }
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param mixed $resource
     * @param string $attribute
     *
     * @return mixed
     */
    protected function resolveAttribute($resource, $attribute)
    {
        /**
         * @var null|Model $relationInstance
         * @var Field $field
         */

        if ($relationInstance = $resource->$attribute) {
            $resource = Nova::resourceForModel($relationInstance);

            foreach ($this->getFields($relationInstance) as $field) {
                if ($field instanceof HasOne ||
                    $field instanceof HasMany ||
                    $field instanceof BelongsToMany) {
                    $field->meta['inlineMorphTo'] = [
                        'viaResourceId' => $relationInstance->id,
                        'viaResource' => $resource::uriKey()
                    ];
                }
            }

            return $resource;
        }
    }

    public function fill(NovaRequest $request, $model)
    {

        /**
         * @var Model $relatedInstance
         * @var Model $model
         * @var Resource $resource
         * @var Field $field
         */
        $resourceClass = $request->input($this->attribute);

        if ($this->typeUpdateable) {
            if ($model->{$this->attribute} !== null && get_class($model->{$this->attribute}) == get_class($resourceClass::newModel())) {
                // same related model
                $relatedInstance = $model->{$this->attribute};
            } else {
                // model has changed
                $relatedInstance = $resourceClass::newModel();
            }
        } else {
            $relatedInstance = $model->{$this->attribute} ?? $resourceClass::newModel();
        }


        $resource = new $resourceClass($relatedInstance);


        if ($relatedInstance->exists) {
            $resource->validateForUpdate($request);
        } else {
            $resource->validateForCreation($request);
        }

        $fields = $this->getFields($relatedInstance);
        $callbacks = [];

        foreach ($fields as $field) {
            $callbacks[] = $field->fill($request, $relatedInstance);
        }

        $relatedInstance->saveOrFail();
        $model->{$this->attribute}()->associate($relatedInstance);


        return function () use ($callbacks) {
            foreach ($callbacks as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                }
            }
        };
    }

    private function getFields(Model $model): Collection
    {
        $resourceClass = Nova::resourceForModel($model);

        return $this->meta['resources']->where('className', $resourceClass)->first()['fields'];
    }

    public function jsonSerialize(): array
    {

        /**
         * @var NovaRequest $request
         */
        $request = app(NovaRequest::class);
        $originalResource = $request->route()->resource;

        /**
         * Temporarily remap the route resource key so every sub field thinks its being resolved by its original parent
         */
        foreach ($this->meta['resources'] as $resource) {
            $resource['fields'] = $resource['fields']->transform(function ($field) use ($request, $resource) {
                $request->route()->setParameter('resource', $resource['uriKey']);

                return $field->jsonSerialize();
            });
        }

        $request->route()->setParameter('resource', $originalResource);

        return parent::jsonSerialize();
    }
}
