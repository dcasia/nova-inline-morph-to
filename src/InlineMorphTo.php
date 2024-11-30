<?php

declare(strict_types = 1);

namespace DigitalCreative\InlineMorphTo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Http\Resources\DetailViewResource;
use Laravel\Nova\Http\Resources\UpdateViewResource;
use Laravel\Nova\Nova;

class InlineMorphTo extends MorphTo
{
    public $component = 'inline-morph-to';

    public function getRules(NovaRequest $request): array
    {
        $possibleTypes = collect($this->morphToTypes)->map->value->values();

        $attribute = $this->attribute . '_type';
        $resource = Nova::resourceInstanceForKey($request->{$attribute});
        $rules = [];

        if ($resource) {

            $rules = $resource
                ->creationFields($request)
                ->flatMap(fn (Field $field) => $field->getCreationRules($request))
                ->mapWithKeys(fn (array $rules, string $attribute) => [
                    $this->attribute . '__' . $attribute => $rules,
                ]);

        }

        return [
            $attribute => [ $this->nullable ? 'nullable' : 'required', 'in:' . $possibleTypes->implode(',') ],
            ...$rules,
        ];
    }

    public function fill(NovaRequest $request, $model): void
    {
        $resource = Nova::resourceInstanceForKey($request->{$this->attribute . '_type'});

        $model::saving(function (Model $parent) use ($request, $resource) {

            $prefix = sprintf('%s__', $this->attribute);

            $attributes = $request
                ->collect()
                ->filter(fn (mixed $value, string $key) => Str::startsWith($key, $prefix))
                ->mapWithKeys(fn (mixed $value, string $key) => [
                    Str::after($key, $prefix) => $value,
                ]);

            $request = $request->duplicate(request: $attributes->toArray());

            if ($key = $attributes->get('key')) {

                $model = $resource->newModelQuery()->whereKey($key)->firstOrFail();

            } else {

                $model = $resource->model();

            }

            $resource->fill(request: $request, model: $model);
            $model->save();

            $parent->{$this->attribute}()->associate($model);

        });

//        $morphType = $model->{$this->attribute}()->getMorphType();
//
//        $model->{$morphType} = !is_null($instance)
//            ? $this->getMorphAliasForClass(get_class($instance))
//            : null;
//
        $foreignKey = $this->getRelationForeignKeyName($model->{$this->attribute}());

//        if ($model->isDirty([ $morphType, $foreignKey ])) {
//            $model->unsetRelation($this->attribute);
//        }

        parent::fillInto($request, $model, $foreignKey);
    }

    /**
     * Resolve the field's value.
     *
     * @param mixed $resource
     * @param string|null $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null): void
    {
        $request = resolve(NovaRequest::class);

        if ($request->isResourceDetailRequest() || $request->isUpdateOrUpdateAttachedRequest()) {

            $value = null;

            if ($resource->relationLoaded($this->attribute)) {
                $value = $resource->getRelation($this->attribute);
            }

            if (!$value) {
                $value = $resource->{$this->attribute}()->withoutGlobalScopes()->getResults();
            }

            /**
             * @var ResourceDetailRequest $request
             */

//            $request = resolve(ResourceDetailRequest::class);
            $request = $request->duplicate();
            $resolver = $request->getRouteResolver();

            $request->setRouteResolver(function () use ($resolver, $resource, $value) {

                /**
                 * @var Route $route
                 */
                $route = $resolver();
                $route->setParameter('resource', $this->resolveMorphType($resource));
                $route->setParameter('resourceId', $value?->getKey());

                return $route;

            });


            if ($request instanceof ResourceDetailRequest) {
                $this->value = DetailViewResource::make()->toArray($request);
            }

            if ($request instanceof ResourceUpdateOrUpdateAttachedRequest) {

                $this->value = UpdateViewResource::make()->toArray($request);

                foreach ($this->value[ 'fields' ] as $field) {
                    $field->attribute = $this->attribute . '__' . $field->attribute;
                }

            }

            [ $this->morphToId, $this->morphToType ] = [
                optional($value)->getKey(),
                $this->resolveMorphType($resource),
            ];

//        if ($resourceClass = $this->resolveResourceClass($value)) {
//            $this->resourceName = $resourceClass::uriKey();
//        }
//
//        if ($value) {
//            if (!is_string($this->resourceClass)) {
//                $this->morphToType = $value->getMorphClass();
//                $this->value = (string) $value->getKey();
//
//                if ($this->value != $value->getKey()) {
//                    $this->morphToId = (string) $this->morphToId;
//                }
//
//                $this->viewable = false;
//            } else {
//                $this->morphToResource = new $this->resourceClass($value);
//
//                $this->morphToId = Util::safeInt($this->morphToId);
//
//                $this->value = $this->formatDisplayValue(
//                    $value, Nova::resourceForModel($value),
//                );
//
//                $this->viewable = ($this->viewable ?? true) && $this->morphToResource->authorizedToView(app(NovaRequest::class));
//            }
//        }

        } else {

            parent::resolve($resource, $attribute);

        }
    }

//    /**
//     * Create a new field.
//     *
//     * @param string $name
//     * @param string|callable|null $attribute
//     * @param callable|null $resolveCallback
//     *
//     * @return void
//     */
//    public function __construct($name, $attribute = null, callable $resolveCallback = null)
//    {
//
//        parent::__construct($name, $attribute, $resolveCallback);
//
//        $this->meta = [
//            'resources' => [],
//            'listable' => true,
//        ];
//
//    }
//
//    /**
//     * Format:
//     *
//     * [ SomeNovaResource1::class, SomeNovaResource2::class ]
//     * [ 'Some Display Text 1' => SomeNovaResource2::class, 'Some Display Text 2' => SomeNovaResource2::class ]
//     *
//     * @param array $types
//     *
//     * @return $this
//     */
//    public function types(array $types): self
//    {
//
//        $types = collect($types)->map(function (string $resource, $key) {
//
//            /**
//             * @var Resource $resourceInstance
//             */
//            $resourceInstance = new $resource($resource::newModel());
//
//            return [
//                'className' => $resource,
//                'uriKey' => $resource::uriKey(),
//                'label' => is_numeric($key) ? $resource::label() : $key,
//                'fields' => $this->resolveFields($resourceInstance),
//            ];
//
//        });
//
//        $this->withMeta([ 'resources' => $types->values() ]);
//
//        return $this;
//
//    }
//
//    private function resolveFields(Resource $resourceInstance): Collection
//    {
//
//        /**
//         * @var NovaRequest $request
//         */
//        $request = app(NovaRequest::class);
//        $controller = $request->route()->controller;
//
//        switch (get_class($controller)) {
//
//            case CreationFieldController::class :
//                return $resourceInstance->creationFields($request);
//
//            case UpdateFieldController::class :
//                return $resourceInstance->updateFields($request);
//
//            case ResourceShowController::class :
//                return $resourceInstance->detailFields($request);
//
//            case ResourceIndexController::class :
//                return $resourceInstance->indexFields($request);
//
//        }
//
//        return $resourceInstance->availableFields($request);
//
//    }
//
//    /**
//     * Resolve the field's value for display.
//     *
//     * @param mixed $resource
//     * @param string|null $attribute
//     * @return void
//     */
//    public function resolveForDisplay($resource, $attribute = null)
//    {
//
//        /**
//         * @var null|Model $relationInstance
//         * @var Field $field
//         */
//        $attribute = $attribute ?? $this->attribute;
//
//        parent::resolveForDisplay($resource, $attribute);
//
//        if ($relationInstance = $resource->$attribute) {
//
//            foreach ($this->getFields($relationInstance) as $field) {
//
//                $field->resolveForDisplay($relationInstance);
//
//            }
//
//        }
//
//    }
//
//    /**
//     * Resolve the field's value.
//     *
//     * @param mixed $resource
//     * @param string|null $attribute
//     * @return void
//     */
//    public function resolve($resource, $attribute = null)
//    {
//
//        /**
//         * @var null|Model $relationInstance
//         * @var Field $field
//         */
//        $attribute = $attribute ?? $this->attribute;
//
//        parent::resolve($resource, $attribute);
//
//        if ($relationInstance = $resource->$attribute) {
//
//            foreach ($this->getFields($relationInstance) as $field) {
//
//                if ($field->computed()) {
//
//                    $field->computedCallback = $field->computedCallback->bindTo(
//                        Nova::newResourceFromModel($relationInstance),
//                    );
//
//                }
//
//                $field->resolve($relationInstance);
//
//            }
//
//        }
//
//    }
//
//    /**
//     * Resolve the given attribute from the given resource.
//     *
//     * @param mixed $resource
//     * @param string $attribute
//     *
//     * @return mixed
//     */
//    protected function resolveAttribute($resource, $attribute)
//    {
//        /**
//         * @var null|Model $relationInstance
//         * @var Field $field
//         */
//
//        if ($relationInstance = $resource->$attribute) {
//
//            $resource = Nova::resourceForModel($relationInstance);
//
//            foreach ($this->getFields($relationInstance) as $field) {
//
//                if ($field instanceof HasOne ||
//                    $field instanceof HasMany ||
//                    $field instanceof BelongsToMany) {
//
//                    $field->meta[ 'inlineMorphTo' ] = [
//                        'viaResourceId' => $relationInstance->id,
//                        'viaResource' => $resource::uriKey(),
//                    ];
//
//                }
//
//            }
//
//            return $resource;
//
//        }
//
//    }
//
//    public function fill(NovaRequest $request, $model)
//    {
//
//        /**
//         * @var Model $relatedInstance
//         * @var Model $model
//         * @var Resource $resource
//         * @var Field $field
//         */
//
//        $resourceClass = $request->input($this->attribute);
//        $relatedInstance = $model->{$this->attribute} ?? $resourceClass::newModel();
//        $resource = new $resourceClass($relatedInstance);
//
//        if ($relatedInstance->exists) {
//
//            $resource->validateForUpdate($request);
//
//        } else {
//
//            $resource->validateForCreation($request);
//
//        }
//
//        $fields = $this->getFields($relatedInstance);
//        $callbacks = [];
//
//        foreach ($fields as $field) {
//
//            $callbacks[] = $field->fill($request, $relatedInstance);
//
//        }
//
//        $relatedInstance->saveOrFail();
//
//        $model->{$this->attribute}()->associate($relatedInstance);
//
//        return function () use ($callbacks) {
//
//            foreach ($callbacks as $callback) {
//
//                if (is_callable($callback)) {
//
//                    call_user_func($callback);
//
//                }
//
//            }
//
//        };
//
//    }
//
//    private function getFields(Model $model): Collection
//    {
//        $resourceClass = Nova::resourceForModel($model);
//
//        return $this->meta[ 'resources' ]->where('className', $resourceClass)->first()[ 'fields' ];
//    }
//
//    public function jsonSerialize(): array
//    {
//
//        /**
//         * @var NovaRequest $request
//         */
//        $request = app(NovaRequest::class);
//        $originalResource = $request->route()->resource;
//
//        /**
//         * Temporarily remap the route resource key so every sub field thinks its being resolved by its original parent
//         */
//        foreach ($this->meta[ 'resources' ] as $resource) {
//
//            $resource[ 'fields' ] = $resource[ 'fields' ]->transform(function ($field) use ($request, $resource) {
//
//                $request->route()->setParameter('resource', $resource[ 'uriKey' ]);
//
//                if (is_array($field)) {
//                    return $field;
//                }
//
//                return $field->jsonSerialize();
//
//            });
//
//        }
//
//        $request->route()->setParameter('resource', $originalResource);
//
//        return parent::jsonSerialize();
//
//    }

}
