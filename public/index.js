var app = new Vue({
    el: '#app',
    delimiters: ['${', '}'],
    data: JSON.parse(window.__INITIAL_DATA__),
    methods: {
        dateConvert: function(date) {
            return moment(date, 'YYYYMMDD').format('l')
        },
        dayConvert: function(day) {
            switch (day) {
                case 'Po':
                    return 'Pondělí';
                case 'Út':
                    return 'Úterý';
                case 'St':
                    return 'Středa';
                case 'Čt':
                    return 'Čtvrtek';
                case 'Pá':
                    return 'Pátek';
            }
        }
    }
})