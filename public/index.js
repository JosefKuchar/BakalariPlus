var app = new Vue({
    el: '#app',
    delimiters: ['${', '}'],
    data: JSON.parse(window.__INITIAL_DATA__)
    /*
    data: {
        name: 'Josef Kuchař, S1E',
        schedule: {
            header: [
                {
                    caption: 1,
                    start: '7:45',
                    end: '8:30'
                },
                {
                    caption: 2,
                    start: '8:35',
                    end: '9:20'
                }
            ],
            days: [
                {
                    name: 'Pondělí',
                    date: '28.04.2018',
                    hours: [
                        {
                            shortName: 'IKT',
                            shortTeacher: 'Mc'
                        }
                    ]
                }
            ]
        }
    }*/
})