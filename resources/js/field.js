import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((Vue, router, store) => {
    Vue.component('index-inline-morph-to', IndexField)
    Vue.component('detail-inline-morph-to', DetailField)
    Vue.component('form-inline-morph-to', FormField)
})
