Nova.booting((Vue, router, store) => {
    Vue.component('index-inline-morph-to', require('./components/IndexField'))
    Vue.component('detail-inline-morph-to', require('./components/DetailField'))
    Vue.component('form-inline-morph-to', require('./components/FormField'))
})
