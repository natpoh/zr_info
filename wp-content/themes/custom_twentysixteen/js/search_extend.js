
var search_extend = search_extend || {};

search_extend.init = function () {
    var $ = jQuery;

    critic_search.add_no_submit('stacking');

    var data = {
        search_extend: 'ajax',
        type: 'movie',

    };
    search_extend.init_more('#page-content', data, 1);

    if (typeof search_extend_data === 'undefined') {
        return false;
    }
    var chart_div = $('#chart_div');
    if (chart_div.hasClass('init')) {
        return false;
    }
    chart_div.addClass('init');

    var tab = chart_div.attr('data-tab');
    var title = chart_div.attr('data-graph-title');
    var y_axis = chart_div.attr('data-y');
    var format = '<span>{series.name}</span>: <b>({point.percentage:.0f}%)</b><br/> $ {point.y}';

    if (tab == 'international') {
        search_extend.init_chart_default(chart_div, title, y_axis, format);
    } else if (tab == 'ethnicity') {
        var vis = chart_div.attr('data-vis');

        search_extend.init_chart_scatter_xy(chart_div, vis);

        // Setup sort
        $('.facet .sort_data:not(.init)').each(function (i, v) {
            var v = $(v);
            v.addClass('init');
            var id = v.attr('id');
            var el = document.getElementById(id);

            new Sortable(el, {
                ghostClass: 'emptyitem',
                draggable: ".sortitem",
                animation: 150,
                onSort: function (evt) {
                    search_extend.change_sort(id);
                },
            });

            v.find('.sortitem').click(function () {
                $(this).toggleClass('disabled');
                search_extend.change_sort(id);
                return false;
            });
        });


    } else if (tab == 'population') {
        var from = chart_div.attr('data-from');
        var to = chart_div.attr('data-to');
        search_extend.init_chart_world(from, to);
    } else if (tab == 'worldmap') {
        var from = chart_div.attr('data-from');
        var to = chart_div.attr('data-to');
        search_extend.init_chart_worldmap(from, to);
    } else if (tab == 'power') {
        search_extend.init_chart_power(chart_div);
    } else if (tab == 'powerrace') {
        search_extend.init_chart_powerrace(chart_div);
    }
};

search_extend.init_chart_default = function (chart_div, title, y_axis, format) {
    var stack_data = {
        def: {title: 'Normal stacking', type: 'normal'},
        active: {title: 'Stacking by percent', type: 'percent'}
    };

    var stacking = search_extend.get_current_stack(stack_data);

    var chart = Highcharts.chart('chart_div', {
        chart: {
            type: 'column',
            zoomType: 'x',
            height: 500
        },
        title: {text: title},
        legend: {
            maxHeight: 70,
        },
        xAxis: {
            startOnTick: true,
            endOnTick: true,
        },
        yAxis: {
            title: {text: y_axis}
        },
        tooltip: {
            pointFormat: format,
            //shared: true
        },
        plotOptions: {
            column: {
                stacking: stacking,
            },
            series: {
                // pointPadding: 0, // Defaults to 0.1
                groupPadding: 0.02, // Defaults to 0.2
                cursor: 'pointer',
                point: {
                    events: {
                        click: function (e) {
                            var m_id = e.point.x;
                            search_extend.click_to_graph(m_id, 'y');
                            return false;
                        }
                    }
                }
            },
        },
        series: search_extend_data
    });

    chart.stacking = stacking;
    chart.stack_data = stack_data;
    chart.update = function () {
        var chart = this;
        var s = chart.series;
        var sLen = s.length;
        for (var i = 0; i < sLen; i++) {
            s[i].update({
                stacking: chart.stacking
            }, false);
        }
        chart.redraw();
    };

    search_extend.init_change_stack(chart);
}

search_extend.theme_xy = function (format, value) {
    if (format == "date") {
        var date = new Date(value);
        value = date.toLocaleDateString("en-US");
    } else if (format == "usd") {
        value = "$" + search_extend.k_m_b_generator(value);
    } else if (format == "percent") {
        value = value + '%';
    } else {
        value = search_extend.k_m_b_generator(value);
    }
    return value;
}

search_extend.k_m_b_generator = function ($num) {
    if ($num > 999 && $num < 99999) {
        $num = ($num / 1000).toFixed(2) + " K";
    } else if ($num > 99999 && $num < 999999) {
        $num = ($num / 1000).toFixed(2) + " K";
    } else if ($num > 999999 && $num < 999999999) {
        $num = ($num / 1000000).toFixed(2) + " M";
    } else if ($num > 999999999 && $num < 999999999999) {
        $num = ($num / 1000000000).toFixed(2) + " B";
    } else if ($num > 999999999999) {
        $num = ($num / 1000000000000).toFixed(2) + " T";
    }
    return $num;
}

search_extend.init_chart_scatter_xy = function (chart_div, vis = '') {
    var title = chart_div.attr('data-title');

    var x_title = chart_div.attr('data-xtitle');
    var x_axis = chart_div.attr('data-xaxis');
    var x_axis_type = chart_div.attr('data-xaxist');
    var x_format = chart_div.attr('data-xformat');
    var x_size = chart_div.attr('data-xsize');
    var xmin = parseInt(chart_div.attr('data-xmin'));
    var xmax = parseInt(chart_div.attr('data-xmax'));

    var y_title = chart_div.attr('data-ytitle');
    var y_axis = chart_div.attr('data-yaxis');
    var y_axis_type = chart_div.attr('data-yaxist');
    var y_format = chart_div.attr('data-yformat');
    var y_size = chart_div.attr('data-ysize');
    var ymin = parseInt(chart_div.attr('data-ymin'));
    var ymax = parseInt(chart_div.attr('data-ymax'));

    var zoomType = 'xy';
    var stacking = 'normal';

    if (vis == 'column' || vis == 'line') {
        // column
        if (vis == 'column') {
            zoomType = 'x';
        }

        if (y_size > 1) {
            ymax = null;
        }
        if (x_size > 1) {
            stacking = '';
        }


    }

    var init_chart = function () {

        var regrassion_array = new Object();

        if (vis == 'regression' || vis == 'bellcurve' || vis == 'plurbellcurve' || vis == 'column' || vis == 'line') {
            var append_data = [];

            for (var i in search_extend_data) {
                var item = search_extend_data[i];
                if (vis == 'column' || vis == 'line') {
                    item.data.sort(function (a, b) {
                        return a['x'] - b['x'];
                    });
                    if (x_size > 1) {
                        item.type = '';
                    }
                    search_extend_data[i] = item;

                } else if (vis == 'regression') {
                    var reg_array = [];
                    for (var i in item.data) {
                        reg_array.push([item.data[i]['x'], item.data[i]['y']]);
                    }
                    var resultr = regression.linear(reg_array, {precision: 100});
                    var points = resultr.points;
                    points.sort(function (a, b) {
                        return a[0] - b[0];
                    });
                    regrassion_array[item.name] = (resultr.string);
                    append_data.push({type: 'spline', color: item.color, name: item.name, data: points});
                } else {
                    search_extend_data[i].id = item.name;
                    append_data.push({
                        name: item.name,
                        type: 'bellcurve',
                        baseSeries: item.name,
                        color: item.color,
                        zIndex: -1,
                        xAxis: 0,
                        yAxis: 1,
                        marker: {enabled: false},
                        pointsInInterval: 5,
                        intervals: 4,
                    });
                    if (vis == 'plurbellcurve') {
                        for (var j in item.data) {
                            var x = item.data[j]['x'];
                            var y = item.data[j]['y'];
                            search_extend_data[i].data[j]['x'] = y;
                            search_extend_data[i].data[j]['y'] = x;
                        }
                    }
                }
            }
            if (vis == 'regression') {
                search_extend_data = search_extend_data.concat(append_data);
            } else {
                if (vis == 'plurbellcurve') {
                    var xtype = x_axis_type;
                    var ytype = y_axis_type;
                    var xtitle = x_title;
                    var ytitle = y_title;
                    var xformat = x_format;
                    var yformat = y_format;
                    x_axis_type = ytype;
                    y_axis_type = xtype;
                    x_title = ytitle;
                    y_title = xtitle;
                    x_format = yformat;
                    y_format = xformat;
                }
                search_extend_data = append_data.concat(search_extend_data);
            }
        }

        var chart_data = {
            chart: {
                zoomType: zoomType,
                height: 500
            },
            title: {
                text: title
            },

            xAxis: {
                title: x_title,
                type: x_axis_type,
            },
            yAxis: {
                title: y_title,
                type: y_axis_type,
            },
            legend: {
                maxHeight: 70,
            },

            tooltip: {
                formatter: function (tooltip) {
                    var type = this.series.userOptions.type;
                    if (type == 'spline') {
                        return '<p>' + this.series.name + '<br /><i>' + regrassion_array[this.series.name] + '</i></p>';
                    } else {
                        var ptitle = '';
                        if (typeof this.point.title !== 'undefined') {
                            ptitle = '<b>' + this.point.title + '</b><br />';
                        }
                        var format_x = search_extend.theme_xy(x_format, this.x);
                        var format_y = search_extend.theme_xy(y_format, this.y);
                        return this.series.name + '<br />' + ptitle + x_title + ': ' + format_x + '<br />' + y_title + ': ' + format_y;
                    }
                }
            },

            plotOptions: {
                series: {
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function (e) {
                                var id = e.point.id;
                                var type = 'm';
                                if (typeof e.point.t !== 'undefined') {
                                    type = e.point.t;
                                }

                                // console.log(e);
                                if (id.indexOf('highcharts')) {
                                    search_extend.click_to_graph(id, type);
                                }

                                return false;
                            }
                        }
                    }
                },
                spline: {},
                column: {
                    stacking: stacking,
                },
                scatter: {
                    marker: {
                        radius: 3,
                        states: {
                            hover: {
                                enabled: true,
                                lineColor: 'rgb(100,100,100)'
                            }
                        }
                    },
                    states: {
                        hover: {
                            marker: {
                                enabled: false
                            }
                        }
                    },

                    tooltip: {
                        useHTML: true,
                        headerFormat: '{series.name}<br />',
                    }
                },

            },
            series: search_extend_data
        };

        if (vis == 'bellcurve' || vis == 'plurbellcurve') {

            chart_data.plotOptions.scatter.visible = false;

            chart_data.xAxis = [
                {title: {title: x_title, type: x_axis_type, }, },
                {title: {text: 'Bell curve'}, visible: false, }
            ];
            chart_data.yAxis = [
                {title: y_title, type: y_axis_type, },
                {title: {text: 'Bell curve'}, visible: false, }
            ];
        }

        // XY logic
        if (xmin >= 0) {
            chart_data.xAxis.min = xmin;
        }
        if (xmax >= 0) {
            chart_data.xAxis.max = xmax;
        }
        if (ymin >= 0) {
            chart_data.yAxis.min = ymin;
        }
        if (ymax >= 0) {
            chart_data.yAxis.max = ymax;
        }

        Highcharts.chart('chart_div', chart_data);
    }

    var tpl = '/wp-content/themes/custom_twentysixteen/js';
    if (vis === 'regression') {
        // load regression js
        var third_scripts = {
            regression: tpl + '/regression.min.js'
        };
        use_ext_js(init_chart, third_scripts);
    } else {
        init_chart();
    }

    // Swap xy
    search_extend.init_swapxy(x_axis, y_axis);
}

search_extend.init_swapxy = function (x_axis = '', y_axis = '') {
    $('.swap_xy').click(function (i, v) {

        var xselect = $('#facet-xaxis select');
        var yselect = $('#facet-yaxis select');
        var xaxis = xselect.val();
        var yaxis = yselect.val();

        // Remove old filters
        search_extend.remove_type_filters('xaxis');
        search_extend.remove_type_filters('yaxis');

        critic_search.enable_submit = false;
        // Add filters
        xselect.val(yaxis).change();
        yselect.val(xaxis).change();

        critic_search.enable_submit = true;


        critic_search.submit();
        return false;
    });
}

search_extend.init_chart_world = function (from, to) {
    Highcharts.chart('chart_div', {
        chart: {
            zoomType: 'xy',
            height: 500,

        },
        title: {
            text: 'World population'
        },

        xAxis: {
            title: {
                text: 'Year',

            },
        },
        yAxis: {
            title: {
                text: 'Total',

            },
        },
        legend: {
            maxHeight: 70,
        },
        tooltip: {

            pointFormat: '<strong>{series.name}</strong><br><p>Population: <b>{point.y:.0f}</b></p><br><p>Percent: <b>{point.wpercent} %</b></p>',
        },
        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function (e) {
                            var year = e.point.x;
                            var race = e.point.series.name;
                            var data = {
                                search_extend: 'ajax',
                                type: 'race',
                                race: race,
                                year: year
                            };
                            critic_search.ajax(data, function (rtn) {
                                $('#select-current').html(rtn);

                                var data = {
                                    search_extend: 'ajax',
                                    type: 'country',
                                    year: year,
                                    from: from,
                                    to: to
                                };
                                search_extend.init_more('#select-current', data);
                            });

                            return false;
                        }
                    }
                }
            },
        },
        series: search_extend_data
    });
}

search_extend.init_chart_worldmap = function (from, to) {

    var chart = Highcharts.mapChart('chart_div', {
        chart: {
            map: 'custom/world',
            height: 700,
        },

        title: {
            text: 'Ethnic world map'
        },
        plotOptions: {
            map: {
                allAreas: false,
            },
            tooltip: {
                headerFormat: '<p>{series.name}</p>',
                pointFormat: '{point.content}'
            },

            series: {
                point: {
                    events: {
                        click: function (e) {
                            var code = e.point.code2;
                            var data = {
                                search_extend: 'ajax',
                                type: 'country',
                                code: code,
                                from: from,
                                to: to,
                            };
                            critic_search.ajax(data, function (rtn) {
                                $('#select-current').html(rtn);
                            });
                        }
                    }
                }
            },
        },

        mapNavigation: {
            enabled: true,
            buttonOptions: {
                verticalAlign: 'bottom'
            }
        },
        series: search_extend_data
    });
}

search_extend.init_chart_power = function (chart_div) {
    var min = chart_div.attr('data-min');
    var max = chart_div.attr('data-max');
    var amin = chart_div.attr('data-amin');
    var amax = chart_div.attr('data-amax');

    var stack_data = {
        def: {title: 'Stacking by per capita', type: 'capita'},
        active: {title: 'Stacking by all data', type: 'all'}
    };

    var types = {
        capita: {
            title: 'Purchasing Power Parity (Per Capita)',
            tooltip: '<p>{point.name}</p><br><p>Buying Power (Per Capita): <b>$ {point.value}</b></p><br><p>Buying Power (Total): $ {point.total}</p><br><p>Year: {point.year}</p>',
            color: {
                min: Number(min),
                max: Number(max),
                minColor: '#dbe3ff',
                maxColor: '#000016',
                stops: [
                    [0, '#EFEFFF'],
                    [0.21, '#004c78'],
                    [0.62, '#000322'],
                    [1, '#000000']
                ]
            },
            series: $.extend(true, [], search_extend_data),
            series2: $.extend(true, [], search_extend_c),
            name2: 'Buying Power (Per Capita)'
        },
        all: {
            title: 'Purchasing Power Total',
            tooltip: '<p>{point.name}</p><br><p>Buying Power (Total): <b>$ {point.value}</b></p><br><p>Buying Power (Per Capita): $ {point.total}</p><br><p>Year: {point.year}</p>',
            color: {
                min: Number(amin),
                max: Number(amax),
                minColor: '#dbe3ff',
                maxColor: '#000016',
                stops: [
                    [0, '#EFEFFF'],
                    [0.13, '#004c78'],
                    [0.62, '#000858'],
                    [1, '#000000']
                ]
            },
            series: $.extend(true, [], search_extend_data_all),
            series2: $.extend(true, [], search_extend_c_all),
            name2: 'Buying Power (Total)'
        }
    }

    var stacking = search_extend.get_current_stack(stack_data);

    var chart_data = {
        chart: {
            map: 'custom/world',
            height: 700,
        },
        title: {
            text: types[stacking]['title']
        },
        legend: {
            title: {
                text: 'Buying Power',
            }
        },
        plotOptions: {
            series: {
                point: {
                    events: {
                        click: function (e) {
                            var code2 = e.point.code2;

                            var data = {
                                search_extend: 'ajax',
                                type: 'country',
                                code: code2,
                            };
                            critic_search.ajax(data, function (rtn) {
                                $('#select-current').html(rtn);
                            });

                            return false;
                        }
                    }
                }
            },
        },
        mapNavigation: {
            enabled: true,
            buttonOptions: {
                verticalAlign: 'bottom'
            }
        },
        tooltip: {
            headerFormat: '',
            pointFormat: types[stacking]['tooltip']
        },
        colorAxis: types[stacking]['color'],
        series: [{
                data: types[stacking]['series'],
                joinBy: ['iso-a2', 'code2'],
            }]
    };

    var chart = Highcharts.mapChart('chart_div', chart_data);

    // Stack logic
    chart.stacking = stacking;
    chart.stack_data = stack_data;
    chart.stack_types = types;
    chart.update = function () {
        var chart = this;
        var types = chart.stack_types;
        var stacking = chart.stacking;
        chart.title.update({text: types[stacking]['title']});
        chart.tooltip.update({pointFormat: types[stacking]['tooltip']});
        chart.colorAxis[0].update(types[stacking]['color'], false);
        chart.series[0].update({data: $.extend(true, [], types[stacking]['series'])}, false);
        chart.redraw();
    };

    var chart2_data = {
        chart: {
            type: 'column',
            panning: true,
        },
        title: false,
        plotOptions: {
            series: {
                grouping: false,
                borderWidth: 0,
                point: {
                    events: {
                        click: function (e) {
                            var code2 = e.point.code2;
                            var data = {
                                search_extend: 'ajax',
                                type: 'country',
                                code: code2,
                            };
                            critic_search.ajax(data, function (rtn) {
                                $('#select-current').html(rtn);
                            });

                            return false;
                        }
                    }
                }

            }
        },
        legend: {
            enabled: false
        },
        tooltip: {
            shared: true,
            headerFormat: '<span style="font-size: 15px">{point.point.country}</span><br/>',
            pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y} </b><br/>'
        },
        xAxis: {
            type: 'category',
            max: 10,
            scrollbar: {
                enabled: true
            },
            labels: {
                useHTML: true,
                animate: true,
                formatter: function () {
                    var output = this.value;
                    return '<span class="iflag"><img src="/analysis/country_data/' + output + '.svg" /><span>';
                }
            }
        },
        yAxis: [{
                title: {
                    text: 'Buying Power'
                },
                showFirstLabel: false
            }],
        colorAxis: types[stacking]['color'],
        series: [{
                name: types[stacking]['name2'],
                dataLabels: [{
                        enabled: false,
                        inside: true,
                        style: {
                            fontSize: '16px'
                        }
                    }],
                data: $.extend(true, [], types[stacking]['series2'])
            }],
        exporting: {
            allowHTML: true
        }
    }

    var chart2 = Highcharts.chart('chart_div_2', chart2_data);
    chart2.update = function (chart) {
        var chart2 = this;
        var types = chart.stack_types;
        var stacking = chart.stacking;

        chart2.colorAxis[0].update(types[stacking]['color'], false);
        chart2.series[0].update({data: $.extend(true, [], types[stacking]['series2']), name: types[stacking]['name2']}, false);
        chart2.redraw();
    };


    search_extend.init_change_stack(chart, chart2);
}


search_extend.init_chart_powerrace = function (chart_div) {
    var yearmin = chart_div.attr('data-yearmin');
    var stack_data = {
        def: {title: 'Buying Power Per Capita', type: 'capita'},
        active: {title: 'Buying Power Total', type: 'all'}
    };


    var types = {
        capita: {
            title: 'Buying Power by race Per Capita (' + yearmin + ')',
            name: 'Purchasing Power Per Capita',
            series: search_extend_data,
            series2: search_extend_c,
        },
        all: {
            title: 'Buying Power by race Total (' + yearmin + ')',
            name: 'Purchasing Power Total',
            series: search_extend_data_all,
            series2: search_extend_c_all,
        }
    }

    var stacking = search_extend.get_current_stack(stack_data);

    var chart_data = {
        chart: {
            type: 'column',
            panning: true,
        },
        title: {
            text: types[stacking]['title']
        },

        legend: {
            enabled: false
        },
        tooltip: {
            shared: true,
            headerFormat: '<span style="font-size: 15px">{point.point.name}</span><br/>',
            pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b> {point.y} </b><br>'
        },
        xAxis: {
            type: 'category',

        },
        yAxis: [{
                title: {
                    text: 'Buying Power'
                },
                showFirstLabel: false
            }, {
                opposite: true,
                title: {
                    text: 'Population'
                }
            }],

        series: [{
                name: types[stacking]['name'],
                dataLabels: [{
                        enabled: false,
                        inside: true,
                        style: {
                            fontSize: '16px'
                        }
                    }],
                data: $.extend(true, [], types[stacking]['series'])
            }, {
                name: 'Population',
                color: '#0026ff',
                type: 'spline',
                zIndex: 2,
                data: $.extend(true, [], types[stacking]['series2']),
                marker: {
                    radius: 6,
                    color: '#0026ff',
                    states: {
                        hover: {
                            enabled: true,

                        }
                    }
                },
                lineWidth: 4,
                yAxis: 1,
            }],
        exporting: {
            allowHTML: true
        }
    };

    var chart = Highcharts.mapChart('chart_div', chart_data);

    // Stack logic
    chart.stacking = stacking;
    chart.stack_data = stack_data;
    chart.stack_types = types;
    chart.update = function () {
        var chart = this;
        var types = chart.stack_types;
        var stacking = chart.stacking;
        chart.title.update({text: types[stacking]['title']});
        chart.series[0].update({data: $.extend(true, [], types[stacking]['series']), name: types[stacking]['name']}, false);
        chart.series[1].update({data: $.extend(true, [], types[stacking]['series2'])}, false);
        chart.redraw();
    };

    search_extend.init_change_stack(chart);
}

search_extend.get_current_stack = function (stack_data) {
    var stacking = stack_data.def.type;
    var stacking_actvie = stack_data.active.type;

    if ($('#stacking-' + stacking_actvie).length) {
        stacking = stacking_actvie;
    }
    return stacking;
}

search_extend.init_change_stack = function (chart, chart2 = '') {
    var stack_data = chart.stack_data;
    var stacking = chart.stacking;
    var change_stack = $('.change_stack');
    if (stacking == stack_data.def.type) {
        change_stack.text(stack_data.active.title);
    } else {
        change_stack.text(stack_data.def.title);
    }

    change_stack.click(function () {
        var type = 'stacking';
        var stacking = stack_data.def.type;
        var active_stacking = stack_data.active.type;
        var $this = $(this);

        if ($('#' + type + '-' + active_stacking).length) {
            // Change stack to default            
            critic_search.remove_filter(type, active_stacking);
            $this.html(stack_data.active.title);
        } else {
            search_extend.remove_type_filters(type);
            // Add stack filter
            stacking = stack_data.active.type;
            var title = stacking;
            critic_search.add_filter(type, active_stacking, title.capitalize(), 'all', type, 'Stacking ');
            $this.html(stack_data.def.title);
        }

        chart.stacking = stacking;
        chart.update();
        if (chart2 != '') {
            chart2.update(chart);
        }

        // Update URL
        critic_search.submit('none', 'graph');
        return false;
    });
}

search_extend.remove_filter_no_submit = function (type) {
    if (type == 'stacking') {
        var change_stack = $('.change_stack');
        change_stack.click();
    }
}

search_extend.init_more = function (selector, data, col = 0) {
    $(selector + ' .more .acc:not(.init)').each(function (i, v) {
        var v = $(v);
        var cc = 'collapsed';
        v.addClass('init');
        // Init collapse
        v.click(function () {
            if (v.hasClass(cc)) {
                var more = v.attr('data-more');
                // Init facet       
                data.code = more;
                critic_search.ajax(data, function (rtn) {
                    $('.more .acc:not(.collapsed)').addClass('collapsed');
                    $('#more-content').remove();
                    var td_len = v.closest('tr').find('td').length + col;
                    v.closest('tr').after('<tr id="more-content"><td colspan="' + td_len + '">' + rtn + '</td></tr>');
                    v.removeClass(cc);
                });
            } else {
                v.addClass('collapsed');
                $('#more-content').remove();
            }
        });

    });

    /*$('#select-current .facet').each(function (i, v) {
        var v = $(v), id = v.attr('id');
        critic_search.facet_collapse_update(id, v);
    });*/
}

search_extend.change_sort = function (id) {

    // Get priority

    var sort = $('#' + id);
    var ftype = sort.attr('data-ftype');
    var type = sort.attr('data-name');
    var result = '';
    sort.find('.sortitem').each(function (i, v) {
        var v = $(v);
        var data_id = v.attr('data-id');
        var data_enabled = 1;
        if (v.hasClass('disabled')) {
            data_enabled = 0;
        }
        result += data_id + data_enabled;
    });

    var filter = $('#search-filters [data-type="' + type + '"]');
    //Filter exist?
    if (filter.length) {
        var fid = filter.attr('id');
        var ids = fid.split('-');
        var from = ids[1];
        //remove old filter and add new
        critic_search.remove_filter(type, from);
    }
    var name_pre = 'Priority ';
    var title = result;
    var key = result;

    critic_search.add_filter(type, key, title, ftype, '', name_pre);

    critic_search.submit();
}

search_extend.click_to_graph = function (key, data_type) {
    var select = $('#select-current');
    select.addClass('clicked');

    var type = select.attr('data-name');
    var title = key;
    var title_pre = select.attr('data-title-pre');
    var type_title = select.attr('data-title');
    var ftype = select.attr('data-ftype');

    var filter = $('#search-filters [data-type="' + type + '"]');
    //Filter exist?
    if (filter.length) {
        var fid = filter.attr('id');
        var ids = fid.split('-');
        var from = ids[1];
        //remove old filter and add new
        critic_search.remove_filter(type, from);
    }

    if (data_type == 'y') {
        title = 'year ' + key;
    } else if (data_type == 'm') {
        title = 'movie ' + key;
    } else if (data_type == 'c') {
        title = 'claster ' + key;
    }

    key = data_type + key;

    // Add new filter
    critic_search.add_filter(type, key, title, ftype, type_title, title_pre);
    critic_search.submit('', 'graph');
}

search_extend.init_facet = function (v) {
    var $ = jQuery;
    v.find('select.facet-select').change(function () {
        search_extend.hide_use_axises();
        var $this = $(this);
        var type = $this.attr('name');
        var id = $this.val();

        if ($('#' + type + '-' + id).length) {
            // no action            
            // console.log('no action');
        } else {
            // Remove all filters
            search_extend.remove_type_filters(type);

            if (id != 'def') {
                var title = $this.find(':selected').attr('data-title');
                var ftype = v.attr('data-type');
                var name_pre = $this.attr('data-name-pre');
                critic_search.add_filter(type, id, title, ftype, type, name_pre);
            }
        }
        critic_search.submit();
    });
    v.find('select.facet-select').each(function () {
        search_extend.hide_use_axises();
    });

    // Face popup
    v.find('.more-popup').click(function () {
        // Table logic
        if (typeof filter_mode === 'undefined') {
            return false;
        }
        // Close menu
        var w = $('body').width();
        if (w <= 990) {
            $('#secondary .close').click();
        }

        var cbody = '';
        var chead = '<th colspan="2">DataSet / Verdict</th>';
        var head_ex = false;

        for (var i in filter_mode) {
            if (i == 't') {
                continue;
            }
            cbody += '<tr id="' + i + '">';

            cbody += '<td colspan="2">' + filter_titles[i] + '</td>';
            for (var j in filter_mode[i]) {
                if (!head_ex) {
                    chead += '<th>' + filter_races[j] + '</th>';
                }
                cbody += '<td class="col">';
                cbody += '<input type="text" name="' + j + '" value="' + filter_mode[i][j] + '">';
                cbody += '</td>';
            }
            head_ex = true;
            cbody += '</tr>';
        }

        var buttons = '<div><button id="save-mode" class="save-btn">Save settings</button> <button id="cancel-mode" class="btn-second ">Cancel</button></div><br />';
        var ctable = '<table id="fm_data"><thead><tr>' + chead + '</tr></thead><tbody>' + cbody + '</tbody></table>';

        // Select type logic
        var type_calc = '<div><select id="calc-type" autocomplete="off" name="t">';
        var type_calc_arr = {0: "Summ*", 1: "Top**"};
        for (var i in type_calc_arr) {
            var checked = "";
            if (i == filter_mode['t']) {
                checked = "selected";
            }
            type_calc += '<option value="' + i + '" ' + checked + '>' + type_calc_arr[i] + '</option>';
        }
        type_calc += '</select> Calculate type.</div><br />';
       
        var type_desc = '<div>*Summ - add up all results of the "datasets" and choose the one with the highest score.<br />\n\
**Top - find the best score over a "dataset".</div>';

        // Select mode logic
        var mode_select = '';
        var last_select = localStorage.getItem('an_werdict_weight');
        var curr_filter = $('li.filter[data-type="weight"]').attr('data-id');
        if (typeof curr_filter === "undefined") {
            curr_filter = 0;
        }
        if (last_select !== null) {
            mode_select += '<div><select id="mode-select" autocomplete="off" name="m">';
            var sdata = JSON.parse(last_select);
            var tdata = {};
            var keys = [];
            for (var i in sdata) {
                var sdate = sdata[i][0];
                keys.push(sdate);
                tdata[sdate] = [i, sdata[i][1]];
            }
            keys.sort();
            var len = keys.length;
            mode_select += '<option value="" >Select settings</option>';
            for (var i = 0; i < len; i++) {
                var k = keys[i];
                var date = new Date(parseInt(k));
                var checked ='';
                if (curr_filter==tdata[k][0]){
                    checked ="selected";
                }
                mode_select += '<option value="' + tdata[k][0] + '" '+checked+'>Weigth id: ' + tdata[k][0] + '; from ' + date.toLocaleString() + '</option>';
            }
            mode_select += '</select> Last saved settings</div><br />';
        }

        // Popup logic
        add_popup();
        $('.popup-content').html('<div id="more-popup" class="default_popup"><h2>Race verdict weight settings</h2>' + mode_select + '\n\
Choose the number of points for each type of verdict.<br />' + ctable + type_calc + buttons + type_desc+'</div>');

        // Select click
        $('#mode-select').click(function () {
            var sval = $(this).val();
            if (sval===''){
                return false;
            }
            var fm = sdata[sval][1];
            if (sval in sdata) {
                for (var i in fm) {
                    if (i == 't') {
                        continue;
                    }
                    for (var j in fm[i]) {
                        $('#fm_data #' + i + ' input[name="' + j + '"]').val(fm[i][j]);
                    }
                }
            }
        });


        // Popup actions
        $('#save-mode').click(function () {
            var vdata = {};
            var table = $('#more-popup table');
            table.find('tbody tr').each(function () {
                var tr = $(this);
                var tr_id = tr.attr('id');
                tr.find('td.col input').each(function () {
                    var td = $(this);
                    var td_id = td.attr('name');
                    var value = td.val();
                    if (typeof vdata[tr_id] === "undefined") {
                        vdata[tr_id] = {};
                    }
                    vdata[tr_id][td_id] = value;
                });
            });
            vdata['t'] = $('select#calc-type').val();

            // Send ajax data
            var data = {
                search_extend: 'ajax',
                type: 'verdict',
                data: vdata,
            };
            critic_search.ajax(data, function (rtn) {
                var mode_id = parseInt(rtn);
                var type = 'weight';
                // filter logic
                var filter = $('#search-filters [data-type="' + type + '"]');
                //Filter exist?
                if (filter.length) {
                    var fid = filter.attr('id');
                    var ids = fid.split('-');
                    var from = ids[1];
                    //remove old filter and add new
                    critic_search.remove_filter(type, from);
                }

                // Add new filter
                var title = 'Weight id ';
                var ftype = $('#facet-verdict').attr('data-type');
                critic_search.add_filter(type, mode_id, mode_id, ftype, title, title);
                critic_search.submit();

                // TODO add history of the settings to user meta and cookies
                var currentdate = "" + new Date().getTime();
                var sdata = {};
                if (last_select !== null) {
                    var sdata = JSON.parse(last_select);
                }
                if (!(mode_id in sdata)) {
                    sdata[mode_id] = [currentdate, vdata];
                    var sdata_str = JSON.stringify(sdata);
                    localStorage.setItem('an_werdict_weight', sdata_str);          
                } 
                $('.popup-close').click();
            });

            return false;
        });

        $('#cancel-mode').click(function () {
            $('.popup-close').click();
            return false;
        });

        $('input[id="action-popup"]').click();
        return false;
    });


}

search_extend.hide_use_axises = function () {
    $('select option.hide').removeClass('hide');
    $('#facet-xaxis select option[value="' + $('#facet-yaxis select').val() + '"').addClass('hide');
    $('#facet-yaxis select option[value="' + $('#facet-xaxis select').val() + '"').addClass('hide');
}

search_extend.remove_type_filters = function (type) {
    $('.filters-wrapper').find('.filter[data-type="' + type + '"]').each(function () {
        var id_torm = $(this).attr('data-id');
        critic_search.remove_filter(type, id_torm);
    });
}

search_extend.update_facets = function ($rtn) {
    var $ = jQuery;
    var selectyear = $('#select-current');
    if (selectyear.hasClass('clicked')) {
        selectyear.removeClass('clicked');
        var find = $rtn.find('#select-current').first();
        if (find.length) {
            $('#select-current').replaceWith(find);
        }
        return false;
    }

    var selectmovie = $('#select-movie');
    if (selectmovie.hasClass('clicked')) {
        selectmovie.removeClass('clicked');
        var find = $rtn.find('#select-movie').first();
        if (find.length) {
            $('#select-movie').replaceWith(find);
        }
        return false;
    }

    $rtn.find('#page-facet').each(function (i, v) {
        var v = $(v), id = v.attr('id');
        if ($("#" + id).length !== 0) {
            // update
            $("#" + id).replaceWith(v);
        }
    });
}


search_extend.submit = function (inc, target) {
    var $ = jQuery;
    if (target == 'graph' || inc == 'content') {

    } else {
        var type = 'current';
        var filter = $('#search-filters [data-type="' + type + '"]');
        //Filter exist?
        if (filter.length) {
            var id = filter.attr('id');
            var ids = id.split('-');
            var from = ids[1];
            // remove old filter
            critic_search.remove_filter(type, from);
        }
    }
}


Number.prototype.format = function (n, x, s, c) {
    var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
            num = this.toFixed(Math.max(0, ~~n));
    return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
};