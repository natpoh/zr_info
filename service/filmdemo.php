
<div class="container">
    <h1>Box Office comparison</h1>
    <div class="canvas-container" style="    width: 100%;height: 400px;">
    <canvas style="    width: 100%;height: 400px;"  id="boxOfficeChart"></canvas>
    </div>
    <h1>Release Date / Ethnicity</h1>
    <div class="canvas-container" style="    width: 100%;height: 400px;">
        <canvas  style="    width: 100%;height: 400px;"  id="ethnicityChart"></canvas>
    </div>



    <h1>Film Search</h1>
    <input type="text" id="searchInput" placeholder="Enter film name..." value="Matrix">
    <button onclick="searchFilms()">Search</button>
    <ul id="filmList"></ul>
    <div id="pagination" class="pagination"></div>
</div>

<style type="text/css">
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }


    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
        text-align: center;
    }

    input[type="text"] {
        padding: 8px;
        font-size: 16px;
        width: 70%;
    }

    button {
        padding: 8px 16px;
        font-size: 16px;
    }

    ul {
        list-style: none;
        padding: 0;
    }

    li.film-item {
        margin-bottom: 75px;
    }

    .pagination {
        text-align: center;
        margin-top: 20px;
    }

    .pagination button {
        margin: 0 3px;
        padding: 5px 10px;
        font-size: 14px;
        cursor: pointer;
    }
.graph {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
}

    .graph canvas{
        max-width: 320px;
        max-height: 320px;
    }

    .cast-list {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .cast-list th,
    .cast-list td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .cast-list th {
        background-color: #f2f2f2;
    }

    .cast-list tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .cast-list tbody tr:hover {
        background-color: #f1f1f1;
    }
    .canvas-container{
        width: max-content;
        text-align: center;
        display: inline-block;
        min-width: 320px;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
    const baseUrl = 'https://api.filmdemographics.com/v1/search';
    let currentPage = 1;

    function searchFilms() {
        const searchInput = document.getElementById('searchInput').value.trim();
        if (searchInput !== '') {
            fetchFilms(searchInput, currentPage);
        }
    }

    function fetchFilms(searchQuery, page) {
        const resultsPerPage = 20;
        const url = `${baseUrl}?s=${searchQuery}&p=${page}&pp=${resultsPerPage}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                displayFilms(data.data);
                displayPagination(data.totalPages);
            })
            .catch(error => console.error('Error fetching films:', error));
    }
    function createChart(canvas, labels, percentages, titleText) {
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: percentages,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(128, 0, 128, 0.7)',
                        'rgba(0, 128, 0, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(128, 0, 128, 1)',
                        'rgba(0, 128, 0, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: titleText
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                               return `${tooltipItem.label}: ${tooltipItem.formattedValue} %`;
                            }
                        }
                    }
                }
               }
        });
    }
    function createCanvasWithHeader(parentElement, demographicData, titleText) {
        const container = document.createElement('div');
        container.classList.add('canvas-container');

        const canvas = document.createElement('canvas');
        canvas.classList.add('chart');

        const title = document.createElement('h3');
        title.textContent = titleText;

        const percentages = demographicData.map(item => item.percent);
        const hasData = percentages.some(percent => percent !== 0);

        if (hasData) {
            container.appendChild(canvas);
            container.appendChild(title);
            parentElement.appendChild(container);

            const labels = demographicData.map(item => item.race);
            createChart(canvas, labels, percentages);
        } else {
            const noDataText = document.createElement('p');
            noDataText.textContent = 'No data';
            container.appendChild(noDataText);
            parentElement.appendChild(container);
        }
    }

    async function loadCastDetails(filmId) {
        const actorContainer = document.querySelector(`.actor_container[data-id="${filmId}"]`);
        try {
            const response = await fetch(`https://api.filmdemographics.com/v1/media/${filmId}/cast`);
            const castData = await response.json();

            if (Array.isArray(castData) && castData.length > 0) {
                const castTable = document.createElement('table');
                castTable.classList.add('cast-list');

                const tableHead = document.createElement('thead');
                const tableHeadRow = document.createElement('tr');
                ['name', 'gender', 'race'].forEach(headerText => {
                    const th = document.createElement('th');
                    th.textContent = headerText;
                    tableHeadRow.appendChild(th);
                });
                tableHead.appendChild(tableHeadRow);
                castTable.appendChild(tableHead);

                const tableBody = document.createElement('tbody');
                castData.forEach(actor => {
                    const tableRow = document.createElement('tr');
                    ['name', 'gender', 'race'].forEach(property => {
                        const tableData = document.createElement('td');
                        tableData.textContent = actor[property];
                        tableRow.appendChild(tableData);
                    });
                    tableBody.appendChild(tableRow);
                });
                castTable.appendChild(tableBody);

                actorContainer.appendChild(castTable);
            } else {
                const noDataText = document.createElement('p');
                noDataText.textContent = 'No cast details available.';
                actorContainer.appendChild(noDataText);
            }
        } catch (error) {
            console.error('Error fetching cast details:', error);
        }
    }

    function toggleCastDetails(filmId) {
        const actorContainer = document.querySelector(`.actor_container[data-id="${filmId}"]`);
        if (!actorContainer.innerHTML.length) {
            loadCastDetails(filmId);
        } else {
            actorContainer.style.display = actorContainer.style.display === 'none' ? 'block' : 'none';
        }
    }
    function displayFilms(films) {
        const filmList = document.getElementById('filmList');
        filmList.innerHTML = '';

        films.forEach(film => {
            const filmItem = document.createElement('li');
            filmItem.classList.add('film-item');

            const mainTitle = document.createElement('h2');
            mainTitle.textContent =  `${film.title} (${film.year})`;

            const detailsButton = document.createElement('button');
            detailsButton.textContent = 'Actors';
            detailsButton.addEventListener('click', () => {

                toggleCastDetails(film.id);

            });


            filmItem.appendChild(mainTitle);

            const canvasblock = document.createElement('div');
            canvasblock.classList.add('graph');
            createCanvasWithHeader(canvasblock, film.cast_stars.demographic, 'Stars Cast');
            createCanvasWithHeader(canvasblock, film.cast_stupporting.demographic, 'Supporting Cast');
            createCanvasWithHeader(canvasblock, film.cast_all.demographic, 'All Cast');
            filmItem.appendChild(canvasblock);


            filmItem.appendChild(detailsButton);

            const newActorContainer = document.createElement('div');
            newActorContainer.classList.add('actor_container');
            newActorContainer.dataset.id = film.id;
            filmItem.appendChild(newActorContainer);

            filmList.appendChild(filmItem);
        });
    }

    function displayPagination(totalPages) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.addEventListener('click', () => {
                currentPage = i;
                searchFilms();
            });
            pagination.appendChild(pageButton);
        }
    }
    searchFilms();



    document.addEventListener('DOMContentLoaded', function () {



        async function load_data() {

            try {
                const response = await fetch(`https://api.filmdemographics.com/v1/string_uri/chart?strURI=%2Fanalytics%2Frelease_1960-2024`);
                const jsonData = await response.json();


                if (jsonData){

                    var labels = [];
                    var domesticData = [];
                    var internationalData = [];

                    jsonData.results.forEach(function (result) {
                        result.data.forEach(function (datapoint) {
                            if (!labels.includes(datapoint.x)) {
                                labels.push(datapoint.x);
                            }
                        });
                    });

                    labels.sort();

                    labels.forEach(function (label) {
                        var domesticValue = null;
                        var internationalValue = null;

                        jsonData.results.forEach(function (result) {
                            result.data.forEach(function (datapoint) {
                                if (datapoint.x === label) {
                                    if (result.title === "Box Office Domestic") {
                                        domesticValue = datapoint.y;
                                    } else if (result.title === "Box Office International") {
                                        internationalValue = datapoint.y;
                                    }
                                }
                            });
                        });

                        domesticData.push(domesticValue);
                        internationalData.push(internationalValue);
                    });

                    var ctx = document.getElementById('boxOfficeChart').getContext('2d');
                    var boxOfficeChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Box Office Domestic',
                                    backgroundColor: '#f69876',
                                    data: domesticData,
                                    stack: 'stack1'
                                },
                                {
                                    label: 'Box Office International',
                                    backgroundColor: '#73e084',
                                    data: internationalData,
                                    stack: 'stack1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                xAxes: [{
                                    stacked: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Year'
                                    }
                                }],
                                yAxes: [{
                                    stacked: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: jsonData.yaxis
                                    },
                                    ticks: {
                                        beginAtZero: true,
                                        callback: function (value, index, values) {
                                            return value.toLocaleString();
                                        }
                                    }
                                }]
                            },

                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(tooltipItem, data) {
                                            return `${tooltipItem.label}:  $ ${tooltipItem.formattedValue}`;
                                        }
                                    }
                                }
                            },

                            title: {
                                display: true,
                                text: jsonData.title
                            }
                        }
                    });



                }else {
                    console.error('no data:');
                }
            } catch (error) {
                console.error('Error fetching cast details:', error);
            }
        }
        function wrapKeysInQuotes(dataString) {
            dataString =  dataString.replace(/([{,]\s*)([a-zA-Z0-9_]+)(\s*:)/g, "$1'$2'$3");
            return dataString.replace(/'([^']+)'/g, '"$1"');
        }

        async function load_ethnic() {

            try {
                const response = await fetch(`https://api.filmdemographics.com/v1/string_uri/chart?strURI=%2Fanalytics%2Ftab_ethnicity%2Fcurrent_y2020%2Frelease_1960-2024`);
                const jsonData = await response.json();


                if (jsonData){

                    var labels = jsonData.results[0].data.map(function (item) {

                        return item.id ? item.id : undefined;
                    }).filter(function (id) {

                        return id !== undefined;
                    });
                    var datasets = [];

                    jsonData.results.forEach(function (result) {


                        var data = result.data.map(function (item) {

                          item=  wrapKeysInQuotes(item);
                         let itemob =JSON.parse(item);
                            if (itemob)
                            {
                             return { x: (itemob.id), y: itemob.y };
                            }

                        });

                        datasets.push({
                            label: result.title,
                            data: data,
                            backgroundColor: result.color,
                            stack: 'stack2' // ƒобавлен параметр stack дл€ каждого датасета
                        });
                    });


                    var ctx = document.getElementById('ethnicityChart').getContext('2d');
                    var ethnicityChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            title: {
                                display: true,
                                text: jsonData.title
                            },
                            scales: {
                                xAxes: [{
                                    stacked: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: jsonData.xtitle
                                    },
                                    ticks: {
                                        callback: function(value, index, values) {
                                            return value.toString();
                                        }
                                    }
                                }],
                                yAxes: [{
                                    stacked: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: jsonData.ytitle
                                    },
                                    ticks: {
                                        min: 0,
                                        max: 100,
                                         }
                                }]
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(tooltipItem, data) {
                                            return `${tooltipItem.label}: ${tooltipItem.formattedValue} %`;
                                        }
                                    }
                                }
                            }
                        }
                    });


                }else {
                    console.error('no data:');
                }
            } catch (error) {
                console.error('Error fetching cast details:', error);
            }
        }
        load_data();
        load_ethnic();
    });




</script>