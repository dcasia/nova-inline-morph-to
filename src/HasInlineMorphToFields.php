<?php

namespace DigitalCreative\InlineMorphTo;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

trait HasInlineMorphToFields
{
    /**
     * Resolve the index fields.
     *
     * @param NovaRequest $request
     *
     * @return Collection
     */
    public function detailFields(NovaRequest $request)
    {

        return tap(parent::detailFields($request)->flatten())->each(function ($field) {

            if ($field instanceof InlineMorphTo) {

                $novaResourceClass = $this->getNovaResourceFromAttribute($field->attribute);

                $resources = $field->meta[ 'resources' ]->where('className', $novaResourceClass)
                                                        ->values()
                                                        ->toArray();
                /**
                 * Filter out all the unnecessary resources
                 */
                foreach ($resources as &$resource) {

                    $resource[ 'fields' ] = collect($resource[ 'fields' ])->filter->showOnDetail->values();

                }

                $field->meta[ 'resources' ] = $resources;

            }

        });

    }

    private function getNovaResourceFromAttribute(string $attribute): string
    {

        $classPath = $this->model()->getAttribute(
            $this->model()->$attribute()->getMorphType()
        );

        return Nova::resourceForModel(
            Relation::getMorphedModel($classPath) ?: $classPath
        );

    }

}
