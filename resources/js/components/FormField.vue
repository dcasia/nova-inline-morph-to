<template>

    <div>

        <default-field :field="field" :errors="errors">

            <template slot="field">

                <select :id="field.attribute"
                        v-model="value"
                        :disabled="shouldDisableTypeSelect"
                        class="w-full form-control form-select"
                        :class="errorClasses">

                    <option selected disabled :value="null">
                        {{__('Choose an option')}}
                    </option>

                    <option v-for="resource in field.resources"
                            :value="resource.className"
                            :selected="resource.className === value">

                        {{ resource.label }}

                    </option>

                </select>

                <p v-if="hasError" class="my-2 text-danger">
                    {{ firstError }}
                </p>

            </template>

        </default-field>

        <div v-for="resource in field.resources" v-if="resource.className === value">

            <div v-for="resourceField in resource.fields">

                <component :is="`form-${ resourceField.component }`"
                           :resource-name="resource.uriKey"
                           :field="resourceField"
                           :errors="errors"/>

            </div>

        </div>

    </div>

</template>

<script>

    import { FormField, HandlesValidationErrors } from 'laravel-nova'

    export default {
        mixins: [ FormField, HandlesValidationErrors ],
        props: [ 'resourceName', 'resourceId', 'field' ],
        computed: {
            shouldDisableTypeSelect() {
                return this.resourceId
            }
        },
        methods: {
            /*
             * Set the initial, internal value for the field.
             */
            setInitialValue() {
                this.value = this.field.value || null
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            async fill(formData) {

                formData.append(this.field.attribute, this.value)

                this.$children.forEach(component => {

                    if (component.field.attribute !== this.field.attribute) {

                        component.field.fill(formData)

                    }

                })

            },

            /**
             * Update the field's internal value.
             */
            handleChange(value) {
                this.value = value
            }
        }
    }
</script>
