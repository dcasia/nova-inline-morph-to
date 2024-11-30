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

            if (blank($resource)) {
                return $parent->{$this->attribute}()->disassociate();
            }

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

        $foreignKey = $this->getRelationForeignKeyName($model->{$this->attribute}());

        parent::fillInto($request, $model, $foreignKey);
    }

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
             * @var NovaRequest $request
             */
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

            $this->morphToId = optional($value)->getKey();
            $this->morphToType = $this->resolveMorphType($resource);

            if ($resourceClass = $this->resolveResourceClass($value)) {
                $this->resourceName = $resourceClass::uriKey();
            }

        } else {

            parent::resolve($resource, $attribute);

        }
    }
}
