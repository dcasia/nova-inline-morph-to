<template>

    <div class="inline-morph-to">

        <DefaultField
            :field="currentField"
            :show-errors="false"
            :field-name="fieldName"
            :show-help-text="showHelpText"
            :full-width-content="fullWidthContent">

            <template #field>

                <div v-if="hasMorphToTypes" class="flex relative">

                    <select :disabled="(viaRelatedResource && !shouldIgnoresViaRelatedResource) || currentlyIsReadonly"
                            :dusk="`${field.attribute}-type`"
                            :value="resourceType"
                            @change="refreshResourcesForTypeChange"
                            class="block w-full form-control form-input form-control-bordered form-input mb-3">

                        <option value="" selected :disabled="!currentField.nullable">
                            {{ __('Choose Type') }}
                        </option>

                        <option
                            v-for="option in currentField.morphToTypes"
                            :key="option.value"
                            :value="option.value"
                            :selected="resourceType === option.value">

                            {{ option.singularLabel }}

                        </option>

                    </select>

                    <IconArrow class="pointer-events-none absolute text-gray-700 top-[15px] right-[11px]"/>

                </div>

                <label v-else class="flex items-center select-none mt-2">
                    {{ __('There are no available options for this resource.') }}
                </label>

            </template>

        </DefaultField>

        <Card class="divide-y divide-gray-100 dark:divide-gray-700" v-if="availableResources">

            <component
                v-for="(field, index) in availableResources.fields"
                ref="items"
                :index="index"
                :key="`${ field.attribute }_${ index }`"
                :is="`form-${field.component}`"
                :errors="errors"
                :resource-id="resourceId"
                :resource-name="resourceType"
                :related-resource-name="relatedResourceName"
                :related-resource-id="relatedResourceId"
                :field="field"
                :via-resource="viaResource"
                :via-resource-id="viaResourceId"
                :via-relationship="viaRelationship"
                :shown-via-new-relation-modal="shownViaNewRelationModal"
                :form-unique-id="formUniqueId"
                :mode="mode"
                @field-shown="handleFieldShown"
                @field-hidden="handleFieldHidden"
                @field-changed="$emit('field-changed')"
                @file-deleted="handleFileDeleted"
                @file-upload-started="$emit('file-upload-started')"
                @file-upload-finished="$emit('file-upload-finished')"
                :show-help-text="showHelpText"
            />

        </Card>

    </div>

</template>

<script>

    import MorphToField from '@/fields/Form/MorphToField'

    export default {
        extends: MorphToField,
        methods: {
            async refreshResourcesForTypeChange(event) {

                this.resourceType = event?.target?.value ?? event

                this.availableResources = []
                this.selectedResource = ''
                this.selectedResourceId = ''
                this.withTrashed = false

                this.softDeletes = false
                this.determineIfSoftDeletes()

                if (!this.isSearchable && this.resourceType) {

                    if (this.field.morphToType === this.resourceType) {
                        this.selectedResourceId = this.field.morphToId
                    }

                    this.getAvailableResources().then(() => {

                        this.emitFieldValueChange(`${ this.fieldAttribute }_type`, this.resourceType)
                        this.emitFieldValueChange(this.fieldAttribute, null)

                    })

                }

            },
            fill(formData) {

                for (const field of this.$refs.items) {
                    field.fill(formData)
                }

                if (this.field.morphToType === this.resourceType) {
                    this.fillIfVisible(formData, `${ this.fieldAttribute }__key`, this.field.morphToId)
                }

                this.fillIfVisible(formData, `${ this.fieldAttribute }_type`, this.resourceType)

            },
        },
    }

</script>
