<?php

namespace DigitalCreative\InlineMorphTo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphOne;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use ReflectionClass;

class InlineMorphTo extends Field
{

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'inline-morph-to';

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

        $useKeysAsLabel = Arr::isAssoc($types);

        $types = collect($types)->map(function (string $resource, $key) use ($useKeysAsLabel) {

            $fields = (new $resource($resource::newModel()))->fields(app(NovaRequest::class));

            return [
                'className' => $resource,
                'label' => $useKeysAsLabel ? $key : $this->convertToHumanCase($resource),
                'fields' => collect($fields)->reject(function ($field) {
                    return $field instanceof ID;
                })->toArray()
            ];

        });

        $this->withMeta([ 'resources' => $types ]);

        return $this;

    }

    private function convertToHumanCase(string $resource): string
    {
        return Str::title(str_replace('_', ' ', Str::snake((new ReflectionClass($resource))->getShortName())));
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
         * @var null|\Illuminate\Database\Eloquent\Model $relationInstance
         * @var \Laravel\Nova\Fields\Field $field
         */

        if ($relationInstance = $resource->$attribute) {

            $fields = $this->getFields($relationInstance);
            $resource = Nova::resourceForModel($relationInstance);

            foreach ($fields as $field) {

                if ($field instanceof MorphOne) {

                    $field->meta[ 'inlineMorphTo' ] = [
                        'viaResourceId' => $relationInstance->id,
                        'viaResource' => $resource::uriKey()
                    ];

                }

                $field->resolve($relationInstance);

            }

            return $resource;

        }

    }

    public function fill(NovaRequest $request, $model)
    {

        /**
         * @var \Illuminate\Database\Eloquent\Model $relatedInstance
         * @var \Illuminate\Database\Eloquent\Model $model
         * @var \App\Nova\Resource $resource
         * @var \Laravel\Nova\Fields\Field $field
         */

        $resourceClass = $request->input($this->attribute);
        $relatedInstance = $model->{$this->attribute} ?? $resourceClass::newModel();
        $resource = new $resourceClass($relatedInstance);

        if ($relatedInstance->exists) {

            $resource->validateForUpdate($request);

        } else {

            $resource->validateForCreation($request);

        }

        $fields = $this->getFields($relatedInstance);

        foreach ($fields as $field) {

            $field->fill($request, $relatedInstance);

        }

        $relatedInstance->saveOrFail();

        $model->{$this->attribute}()->associate($relatedInstance);

    }

    private function getFields(Model $model): array
    {
        $resourceClass = Nova::resourceForModel($model);

        return $this->meta[ 'resources' ]->where('className', $resourceClass)->first()[ 'fields' ];
    }

}
