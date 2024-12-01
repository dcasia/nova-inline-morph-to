import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
    app.component('index-inline-morph-to', IndexField)
    app.component('detail-inline-morph-to', DetailField)
    app.component('form-inline-morph-to', FormField)
})
