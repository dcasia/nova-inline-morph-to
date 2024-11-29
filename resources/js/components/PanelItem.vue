<template>

    <div
        class="flex flex-col -mx-6 px-6 py-2 space-y-2"
        :class="[
          'md:py-0 @sm/peekable:py-0 @md/modal:py-0',
          'md:space-y-0 @sm/peekable:space-y-0 @md/modal:space-y-0',
        ]"
        :dusk="field.attribute">

        <div :class="['@sm/peekable:w-1/4 @md/modal:w-1/4', 'md:py-3 @sm/peekable:py-3 @md/modal:py-3' ]">

            <slot>

                <h4 class="font-normal @sm/peekable:break-all">
                    <span>{{ label }}</span>
                </h4>

            </slot>

        </div>

        <div class="break-all" :class="[ 'lg:break-words @md/peekable:break-words @lg/modal:break-words' ]">

            <slot name="value">

                <CopyButton
                    v-if="fieldValue && field.copyable && !shouldDisplayAsHtml"
                    @click.prevent.stop="copy"
                    v-tooltip="__('Copy to clipboard')">

                      <span ref="theFieldValue">
                        {{ fieldValue }}
                      </span>

                </CopyButton>

                <p v-else-if="fieldValue && !field.copyable && !shouldDisplayAsHtml" class="flex items-center">
                    {{ fieldValue }}
                </p>

                <div v-else-if="fieldValue && !field.copyable && shouldDisplayAsHtml" v-html="fieldValue"/>

                <p v-else>&mdash;</p>

            </slot>

        </div>

    </div>

</template>

<script>

    import PanelItem from '@/components/PanelItem'

    export default {
        extends: PanelItem,
    }

</script>
