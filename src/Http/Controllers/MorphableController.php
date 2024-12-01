<?php

declare(strict_types = 1);

namespace DigitalCreative\InlineMorphTo\Http\Controllers;

use Illuminate\Support\Str;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Controllers\MorphableController as BaseMorphableController;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class MorphableController extends BaseMorphableController
{
    public function __invoke(NovaRequest $request): array
    {
        $relatedResource = Nova::resourceForKey($request->type);

        abort_if(is_null($relatedResource), 403);

        $relationKey = Str::after($request->field, '__');

        $request->newResource()
            ->availableFieldsOnIndexOrDetail($request)
            ->whereInstanceOf(RelatableField::class)
            ->findFieldByAttribute($relationKey, fn () => abort(404))
            ->applyDependsOn($request);

        $resource = Nova::resourceInstanceForKey($request->type);
        $fields = [];

        if ($request->isUpdateOrUpdateAttachedRequest()) {

            $fields = $resource->updateFieldsWithinPanels($request)->applyDependsOnWithDefaultValues($request);

            if ($key = $request->current) {

                $instance = $relatedResource::newModel()->newQuery()->whereKey($key)->firstOrFail();

                $fields->resolve(
                    resource: $relatedResource::make($instance),
                );

            }

        }

        if ($request->isCreateOrAttachRequest()) {

            $fields = $resource->creationFieldsWithinPanels($request)->applyDependsOnWithDefaultValues($request);

        }

        foreach ($fields as $field) {
            $field->attribute = $request->field . '__' . $field->attribute;
        }

        return [
            'resources' => [
                'fields' => $fields,
                'panels' => $resource->availablePanelsForCreate($request, $fields),
            ],
        ];
    }
}