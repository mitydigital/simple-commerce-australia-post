<template>
    <div class="checkboxes-fieldtype">
        <div class="option" v-for="(option, $index) in options" :key="$index">
            <label>
                <input type="checkbox"
                       ref="checkbox"
                       :name="name + '[]'"
                       :value="option.value"
                       :disabled="isReadOnly"
                       v-model="values"
                />
                {{ option.label }}
            </label>
        </div>
    </div>
</template>

<script>

export default {

    mixins: [Fieldtype],

    data() {
        return {
            values: this.value || []
        }
    },

    computed: {
        options() {
            return _.map(this.meta.options, (value, key) => {
                return {
                    'value': key,
                    'label': value
                };
            });
        },

        replicatorPreview() {
            return this.values.map(value => {
                const option = _.findWhere(this.options, {value});
                return option ? option.label : value;
            }).join(', ');
        },
    },

    watch: {

        values(values, oldValues) {
            values = this.sortValues(values);

            if (JSON.stringify(values) === JSON.stringify(oldValues)) return;

            this.update(values);
        },

        value(value) {
            this.values = this.sortValues(value);
        }

    },

    methods: {

        focus() {
            this.$refs.checkbox[0].focus();
        },

        sortValues(values) {
            if (!values) return [];

            return this.options
                .filter(opt => values.includes(opt.value))
                .map(opt => opt.value);
        }

    }
};
</script>