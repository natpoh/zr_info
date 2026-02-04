function getActiveButton() {
    let activeButtonId=0;
    var activeButton = document.querySelector('.menu_lines button.active');
    if (activeButton) {
        activeButtonId = Number(activeButton.dataset.lineId);

    } else {
        console.log('no active id');
    }
    return activeButtonId;
}
function addLine(x,y) {

    let parent =getActiveButton();

    var lastId = object_array.line_point.length > 0 ? object_array.line_point[object_array.line_point.length - 1].id : 0;


    let id = lastId + 1;
    var newCube = {
        id: id,
        x:x,
        y:y,
        tw:tw_press_line,
        parent:parent
    };

    object_array.line_point.push(newCube);
    return [id,parent];
}
function addCube(x,y) {

    var lastId = object_array.cube.length > 0 ? object_array.cube[object_array.cube.length - 1].id : 0;

    let id = lastId + 1;
    var newCube = {
        id: id,
        x:x,
        y:y
    };

    object_array.cube.push(newCube);
    return id;
}


function findPointsById(pointId, parentId) {
    var currentIndex = object_array.line_point.findIndex(function(point) {
        return point.id === pointId && point.parent === parentId;
    });

    var previousPoint = null;

    for (var i = currentIndex - 1; i >= 0; i--) {
        if (object_array.line_point[i].parent === parentId) {
            previousPoint = object_array.line_point[i];
            break;
        }
    }

    var currentPoint = object_array.line_point[currentIndex];

    return {
        current: currentPoint,
        previous: previousPoint
    };
}



///line

function draw_line(result,block)
{

    const distanceX = result.current.x - result.previous.x;
    const distanceY = result.current.y - result.previous.y;

    const hypotenuse = Math.sqrt(Math.pow(distanceX, 2) + Math.pow(distanceY, 2));
    const angle = Math.atan2(distanceY, distanceX) * (180 / Math.PI);

    const line = document.querySelector('.line_point#line_'+block+'');

    line.style.width = hypotenuse + 'px';
    // let tx =0;
    // if (angle<90)
    // {
    //     tx =5;
    // }
    //  if (angle<0)
    // {
    //     tx =-5;
    // }
    // if (angle>120)
    // {
    //     tx =-5;
    // }

    //line.style.transform = `rotate(${angle}deg)  translateY(-13px)  translateX(${tx}px)`;
    line.style.transform = `rotate(${angle}deg)`;
    line.style.left = result.previous.x + 'px';
    line.style.top = result.previous.y + 'px';


}

///line


function    draw_lines(id,parent){

    let result = findPointsById(id, parent);
    // console.log(result);

    if (result.previous)
    {

        draw_line(result,id)

    }


}

// Highcharts



function chart(id,data) {

    //console.log(data);
    Highcharts.chart(id, {
        chart: {

            plotBorderWidth: null,
            plotShadow: false,
            type: 'line'
        },
        title: {
            text: ''
        },

        plotOptions: {
            series: {
                grouping: false,
                borderWidth: 0,
            }
        },
        legend: {
            enabled: false
        },
        xAxis: {

            visible: false,
        },
        yAxis: {

            visible: false,

        },
        tooltip: {
            formatter: function () {

                const formatDate = function (timestamp) {
                    const date = new Date(timestamp);
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    const day = date.getDate().toString().padStart(2, '0');
                    const year = date.getFullYear();
                    return `${month}/${day}/${year}`;
                };


                const formattedDate = formatDate(this.x);
                return `<b>Date:</b> ${formattedDate}<br/><b>Value:</b> ${this.y}`;
            }
            },

        series: [{
            data: data
        }]
    });
    // Highcharts
}



function prepare_request(foundData)
{
    let dop_request='';
    if(foundData.requests)
    {
        for (const key in foundData.requests) {
            if (foundData.requests.hasOwnProperty(key)) {

                let value = foundData.requests[key];

                if (key && value)
                {
                    let subkey = key.substring(2);

                    // console.log(key,subkey,value);

                    if (subkey=='default')
                    {
                        dop_request+='&'+value;
                    }

                    else  if (subkey && request_array[subkey])
                    {
                        dop_request+='&'+value+'='+request_array[subkey];

                    }

                }
            }
        }
    }

    return dop_request;
}
function fetchChartData(id,datavalue,dop_request,period,last_update) {

    if (!last_update){last_update='last_update';}
    let endDate = Date.now();
    let startDate =  Number(endDate) - period * 24 * 60 * 60 * 1000;

    const url = '../analysis/jqgrid/get.php';
    const params = {
        startDate: startDate,
        endDate: endDate,
        db: datavalue,
        row: last_update,
        request_string: dop_request,
        oper: 'get_graph',
        groupType: 'daily'
    };

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(params),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {

            chart(id,data);

        })
        .catch(error => {

            console.error('There was a problem with the fetch operation:', error);
        });
}


function show_charts(block_id)
{
let elements;

    if (block_id)
    {
        elements = document.querySelectorAll('#cube_'+block_id+' .mb_graph.not_load');
    }
    else
    {
      elements = document.querySelectorAll('.mb_graph.not_load');
    }


    elements.forEach(element => {

        let id = element.getAttribute('id');
        let dataId = element.getAttribute('data-id');
        let period = element.getAttribute('data-value');



        let foundData = getCubeDataById(dataId);
        let datavalue  = foundData.table;
        let dop_request=prepare_request(foundData);
        let last_update  = foundData.last_update;

        fetchChartData(id,datavalue,dop_request,period,last_update);

        element.classList.add('loaded');

      ///  console.log(`Element ID: ${id}, Data ID: ${dataId}`);
   });


}
function inner_message(id,inner_data=[])
{
    console.log(inner_data);

    let inner_message ='';
    if (inner_data)
    {
        if (inner_data.title)
        {
            inner_message+='<p class="mb_title">'+inner_data.title+'</p>';
        }
        if (inner_data.desc)
        {
            inner_message+='<p class="mb_desc">'+inner_data.desc+'</p>';
        }
        if (inner_data.table)
        {
            inner_message+='<p class="mb_table"><button data-id="'+id+'" class="open_btn">'+inner_data.table+'</button></p>';
        }
        if (inner_data.graph)
        {
            inner_message+='<p data-id="'+id+'" data-value="'+inner_data.graph+'" id="chart-container-'+id+'" class="mb_graph not_load"></p>';
        }

        if (inner_data.link)
        {
            let getRequest='';

           ///console.log(inner_data.requests,request_array);
            if (inner_data.requests)
            {
                const result = {};

                for (let key in request_array) {
                    const requestKey = `b_${key}`;
                    if (inner_data.requests.hasOwnProperty(requestKey)) {
                        result[inner_data.requests[requestKey]] = request_array[key];
                    }
                }
              ///  console.log(result);

                let req_default='';

                if (inner_data.requests.b_default)
                {
                    req_default = inner_data.requests.b_default;
                }

                if (result || req_default)
            {
                let params =req_default;
                if (result)
                {
                    params+=  new URLSearchParams(result).toString();
                }


                if (inner_data.link.includes('?')) {
                    getRequest = `&${params}`;
                } else {
                    getRequest = `?${params}`;
                }
            }
            }

            inner_message+='<p class="mb_link"><a target="_blank" href="'+inner_data.link+getRequest+'" class="ext_link">Link</a></p>';
        }
    }

    inner_message+='<p class="mb_edit">id:'+id+' <button data-id="'+id+'" class="edit_cube">Edit</button></p>';

    return inner_message;
}


function insert_block_to_field(id,x,y,inner_data=[])
{

    let inner_message_text=inner_message(id,inner_data)


    let block_html =`<div class="cube" id="cube_${id}" style="left: ${x}px; top: ${y}px;">
                <div class="face front"></div>
<!--                <div class="face right"></div>-->
                <div class="face left"></div>
<!--                <div class="face top"></div>-->
                <div class="face bottom"></div>
                <div class="cube_desc">
                    <div class="cube_desc_message">${inner_message_text}</div>
                </div>
            </div>`;


    var isoBlock = document.querySelector('.iso');
    isoBlock.insertAdjacentHTML('beforeend', block_html);

    if (inner_data)
    {
        if (inner_data.color)
        {
            document.querySelector('.cube#cube_'+id).classList.add('color_'+inner_data.color);
        }
        if (inner_data.method)
        {
            document.querySelector('.cube#cube_'+id).classList.add('method_'+inner_data.method);
        }
    }
    if (inner_data)
    {
       // console.log(inner_data);
        if (inner_data.type)
        {
            let tclass   = inner_data.type;
            if (tclass.includes(" ")) {
                tclass   = tclass.replace(/ /g, "_");
            }

           // console.log(inner_data.type);
            document.querySelector('.cube#cube_'+id).classList.add('type_'+tclass);
        }

    }
   show_charts(id);
}


function move_line(targetX,targetY,newX,newY)
{
    let parents =[];

    var updatedPoints = object_array.line_point.map(function(point) {
        if (point.x === targetX && point.y === targetY) {

            point.x = newX;
            point.y = newY;

            parents.push(point.parent);

        }
    });



    if (object_array.line_point)
    {
        if (object_array.line_point[0])
        {
            object_array.line_point.forEach(function(inner_data) {
                if (parents.includes(inner_data.parent)) {

                    document.querySelector('.line_point#line_'+inner_data.id).remove();


                    let tw = false;
                    if (inner_data.tw)
                    {
                        tw= inner_data.tw;
                    }
                    insert_line_to_field(inner_data.id,inner_data.parent,inner_data.x,inner_data.y,tw);
                }
            });
        }
    }

}


function move_block(x,y,id)
{

    let indexToUpdate = object_array.cube.findIndex(function(item) {
        return item.id === id;
    });

    if (indexToUpdate !== -1) {
        let targetX=object_array.cube[indexToUpdate].x;
        let targetY=object_array.cube[indexToUpdate].y;

        object_array.cube[indexToUpdate].x = x;
        object_array.cube[indexToUpdate].y = y;

        // console.log('target ',targetX,targetY,' new point ',point.x ,point.y );
        move_line(targetX,targetY,x,y);
    }



    document.querySelector(`.cube#cube_${id}`).remove();

    let inner_data =getCubeDataById(id);

    insert_block_to_field(id,x,y,inner_data);


}

function add_block(x,y){
    let id  = addCube(x,y);
    insert_block_to_field(id,x,y);

}


function insert_line_to_field(id,parent,x,y,tw)
{

    if (tw)
    {
        tw = ' two_ways ';
    }
    else
    {
        tw='';
    }

    let block_html =`<div class="line_point line${tw}" id="line_${id}" style="left: ${x}px; top: ${y}px;"></div>`;
    var isoBlock = document.querySelector('.iso');
    isoBlock.insertAdjacentHTML('beforeend', block_html);
    //draw lines
    draw_lines(id,parent);


}




function add_block_line(x,y){
    let [id,parent]  = addLine(x,y);

    let tw =false;
    if (tw_press_line)
    {
        tw = true;
    }

    insert_line_to_field(id,parent,x,y,tw);

}






const mainwin = document.querySelector('.main_win');
let scale = 0.3; // начальный масштаб
var deltaX_last = 0, deltaY_last = 0;


mainwin.addEventListener('wheel', function (event) {
    if (event.target.closest('.iso')) {
        const delta = event.deltaY || event.detail || event.wheelDelta;

        if (delta < 0) {
            scale += 0.05; // увеличиваем масштаб на 0.1
        } else {
            scale -= 0.05; // уменьшаем масштаб на 0.1
        }

        scale = Math.max(0.1, Math.min(2, scale));


        mainwin.style.transform = `scale(${scale})`;

        event.preventDefault();
    }
});


const mainScroll = document.querySelector('.main_scroll');
let isMouseDown = false;
let startX, startY, scrollLeft, scrollTop;

mainScroll.addEventListener('mousedown', function (event) {
    isMouseDown = true;
    startX = event.pageX - mainScroll.offsetLeft;
    startY = event.pageY - mainScroll.offsetTop;
    scrollLeft = mainScroll.scrollLeft;
    scrollTop = mainScroll.scrollTop;
});


///add data

function removeItemById(id,index) {

    object_array[index] = object_array[index].filter(function(item) {
        return item.id !== id;
    });


}

document.querySelector('.save_button').addEventListener('click', function() {


    console.log(object_array);

    fetch('/service/sheme.php?edit_data='+main_id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(object_array)
    })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok.');
        })
        .then(function(data) {
            console.log(data);
            document.querySelector('.save_result').innerHTML=data.message;

            setTimeout(function() {
                document.querySelector('.save_result').innerHTML = '';
            }, 2000);
        })
        .catch(function(error) {
            console.error('There has been a problem with your fetch operation:', error);
        });

});




const addButton = document.querySelector('.add_cube');

let isButtonPressed = false;
let isButtonMoveBPressed = false;
let isButtonPressed_line = false;
let tw_press_line = false;

addButton.addEventListener('click', function() {
    isButtonPressed = !isButtonPressed;
    document.querySelectorAll('.add-button').forEach(function(element) {
        element.classList.remove('active');
    });
    if (isButtonPressed) {
        addButton.classList.add('active');
        document.querySelector('body').classList.add('add_block');
        document.querySelector('body').classList.remove('add_line');
        document.querySelector('body').classList.remove('move_block');
        isButtonMoveBPressed = false;
        isButtonPressed_line = false;

    } else {
        addButton.classList.remove('active');
        document.querySelector('body').classList.remove('add_block');
    }
});



const moveButton = document.querySelector('.move_cube');


var move_cube_id =0;

moveButton.addEventListener('click', function() {
    isButtonMoveBPressed = !isButtonMoveBPressed;

    document.querySelectorAll('.add-button').forEach(function(element) {
        element.classList.remove('active');
    });



    if (isButtonMoveBPressed) {
        moveButton.classList.add('active');
        document.querySelector('body').classList.add('move_block');

        document.querySelector('body').classList.remove('add_block');
        document.querySelector('body').classList.remove('add_line');
        isButtonPressed = false;
        isButtonPressed_line = false;
    } else {
        moveButton.classList.remove('active');
        document.querySelector('body').classList.remove('move_block');
    }
});

const add_line_button = document.querySelector('.add-line-button');

const tw_line_button = document.querySelector('.line-tw-button');







function check_click_linebutton()
{

    var menuButtons = document.querySelectorAll('.menu_lines button');


    function setActiveButton(clickedButton) {
        menuButtons.forEach(function(button) {
            button.classList.remove('active');
        });
        clickedButton.classList.add('active');
        let lineId = clickedButton.dataset.lineId;

        var filteredElements = object_array.line_point.filter(function(element)

        {
            return element.parent === Number(lineId);
        });

        var ids = filteredElements.map(function(element) {
            return element.id;
        });
        console.log(lineId,filteredElements,ids);

        var allLinePointElements = document.querySelectorAll('.line_point');
        allLinePointElements.forEach(function(element) {
            element.classList.remove('active');
        });

        ids.forEach(function(id) {
            var linePointElement = document.querySelector('.line_point#line_' + id);
            if (linePointElement) {
                linePointElement.classList.add('active');
            }
        });


    }


    menuButtons.forEach(function(button) {
        button.addEventListener('click', function() {

            setActiveButton(button);

            var activeButtonId = button.dataset.lineId;

        });
    });







}


function addNewLine() {

    let lines = document.querySelector('.menu_lines button.active');
    if (lines)
    {
        lines.classList.remove('active');
    }
    let menuLines = document.querySelector('.menu_lines');
    var newLineId = object_array.line.length + 1;
    var newLine = { id: newLineId, name: "Line " + newLineId };
    object_array.line.push(newLine);

    var lineButton = document.createElement('button');
    lineButton.innerText = newLine.name;
    lineButton.dataset.lineId = newLine.id;
    lineButton.classList.add('active');
    menuLines.appendChild(lineButton);


    check_click_linebutton()
}
document.querySelector('.add-new-line-button').addEventListener('click', addNewLine);



tw_line_button.addEventListener('click', function() {

    tw_press_line = !tw_press_line;
    if (tw_press_line) {
        tw_line_button.classList.add('active');
    }
    else {
        tw_line_button.classList.remove('active');
    }
});



add_line_button.addEventListener('click', function() {
    isButtonPressed_line = !isButtonPressed_line;
    document.querySelectorAll('.add-button').forEach(function(element) {
        element.classList.remove('active');
    });
    if (isButtonPressed_line) {
        add_line_button.classList.add('active');
        document.querySelector('body').classList.add('add_line');

        document.querySelector('body').classList.remove('add_block');
        document.querySelector('body').classList.remove('move_block');
        isButtonPressed = false;
        isButtonMoveBPressed = false;


        let menuLines = document.querySelector('.menu_lines');

        if (object_array.line && (object_array.line[0])) {
            object_array.line.forEach(function(line) {
                var lineButton = document.createElement('button');
                lineButton.innerText = line.name; // Используйте имя линии или другие свойства для текста кнопки
                lineButton.dataset.lineId = line.id; // Устанавливаем id линии как data-атрибут кнопки
                menuLines.appendChild(lineButton);

                check_click_linebutton();
            });
            document.querySelector('.menu_lines button:last-of-type').classList.add('active');
        }
        else {

            addNewLine();
        }


    } else {
        document.querySelector('.menu_lines').innerHTML = '';
        add_line_button.classList.remove('active');
        document.querySelector('body').classList.remove('add_line');

        var allLinePointElements = document.querySelectorAll('.line_point');
        allLinePointElements.forEach(function(element) {
            element.classList.remove('active');
        });

    }
});





document.addEventListener('mousemove', function (event) {
    if (!isMouseDown) return;
    const x = event.pageX - mainScroll.offsetLeft;
    const y = event.pageY - mainScroll.offsetTop;
    const moveX = (x - startX) * 1; // умножьте на любое число для изменения скорости скролла
    const moveY = (y - startY) * 1;

    mainScroll.scrollLeft = scrollLeft - moveX;
    mainScroll.scrollTop = scrollTop - moveY;
});

document.addEventListener('mouseup', function () {
    isMouseDown = false;
});


const isoBlock = document.querySelector('.iso');
let isRightMouseDown = false;
let initialMouseX, initialMouseY, initialRotationZ = -45, initialRotationX = 60;



function getCubeDataById(id) {
    id =Number(id);

    return  object_array.cube.find(function(item) {

        return item.id === id;
    });
}
function add_request_data(request_array)
{


    const container = document.querySelector('.rq_container');


    for (const key in request_array) {
        if (request_array.hasOwnProperty(key)) {

            const input = container.querySelector(`input[data-type="${key}"]`);

            if (input) {

                input.value = request_array[key];
            } else {

                const newInput = document.createElement('input');
                newInput.setAttribute('data-type', key);
                //newInput.setAttribute('data-value', request_array[key]);
                newInput.classList.add('edit_sheme_input');
                newInput.value = request_array[key];

                const div = document.createElement('div');
                div.classList.add('b_row');
                div.innerHTML = `${key} => `;
                div.appendChild(newInput);
                container.appendChild(div);

                newInput.addEventListener('change', function() {
                   const dataType = newInput.getAttribute('data-type');
                   const value = newInput.value;
                    save_request(dataType,  value);
                });
            }
        }
    }
}


//var colorInput = document.querySelector('.b_color');
//var methodInput = document.querySelector('.b_method');



// methodInput.addEventListener('change', function(event) {
//     let data = event.target.value;
//     let idElement = document.querySelector('.b_id');
//     var id = parseInt(idElement.textContent);
//     var cube = findCubeById(id);
//
//     if (cube) {
//         cube.method = data;
//     }
//
//     var cubeElement = document.querySelector('.cube#cube_'+id);
//     var classesToRemove = Array.from(cubeElement.classList).filter(className => className.includes('method_'));
//     cubeElement.classList.remove(...classesToRemove);
//
//     cubeElement.classList.add('method_'+data);
//
// });
// colorInput.addEventListener('change', function(event) {
//     var selectedColor = event.target.value;
//     let idElement = document.querySelector('.b_id');
//     var id = parseInt(idElement.textContent);
//     var cube = findCubeById(id);
//
//     if (cube) {
//         cube.color = selectedColor;
//     }
//
//     var cubeElement = document.querySelector('.cube#cube_'+id);
//     var classesToRemove = Array.from(cubeElement.classList).filter(className => className.includes('color_'));
//     cubeElement.classList.remove(...classesToRemove);
//
//     cubeElement.classList.add('color_'+selectedColor);
//
// });

function prepare_data(className, value) {
    let cid = className.substring(2);

    let idElement = document.querySelector('.b_id');
    var id = parseInt(idElement.textContent);
    var cube = findCubeById(id);
    //console.log(cid,cube);
    if (cube) {

        cube[cid] = value;



        if (cid=='title' || cid=='desc' || cid=='table' || cid=='link' || cid=='graph' || cid=='last_update')
        {

             let imsg =  inner_message(id,cube);
            document.querySelector('.cube#cube_'+id+' .cube_desc_message').innerHTML=imsg;

            show_charts(id);
        }
        else if (cid=='type')
        {


            let cubeElement = document.querySelector('.cube#cube_'+id);
            let classesToRemove = Array.from(cubeElement.classList).filter(className => className.includes('type_'));
            cubeElement.classList.remove(...classesToRemove);

            let tclass   = value;
            if (tclass.includes(" ")) {
                tclass   = tclass.replace(/ /g, "_");
            }

            cubeElement.classList.add('type_'+tclass);

            if ( value=='Synch')
            {
                document.querySelector('.b_table').value='commit';
                document.querySelector('.edit_sheme_input[data-type="b_default"]').value='description=';
                document.querySelector('.b_graph').value='30';


                if (!cube.requests)cube.requests={};
                if (!cube.requests.b_default)cube.requests.b_default={};
                cube.requests.b_default='description=';
                cube.table='commit';
                cube.graph='30';

                let imsg =  inner_message(id,cube);
                document.querySelector('.cube#cube_'+id+' .cube_desc_message').innerHTML=imsg;
            }
            else if ( value=='Log')
            {
                document.querySelector('.b_table').value='movies_log';
                document.querySelector('.edit_sheme_input[data-type="b_default"]').value='type=';
                document.querySelector('.b_graph').value='30';


                if (!cube.requests)cube.requests={};
                if (!cube.requests.b_default)cube.requests.b_default={};
                cube.requests.b_default='type=';
                cube.table='movies_log';
                cube.graph='30';

                let imsg =  inner_message(id,cube);
                document.querySelector('.cube#cube_'+id+' .cube_desc_message').innerHTML=imsg;
            }

        }
        else if (cid=='color')
        {
            let cubeElement = document.querySelector('.cube#cube_'+id);
            let classesToRemove = Array.from(cubeElement.classList).filter(className => className.includes('color_'));
            cubeElement.classList.remove(...classesToRemove);

            cubeElement.classList.add('color_'+value);

        }
        else if (cid=='method') {
            var cubeElement = document.querySelector('.cube#cube_'+id);
            var classesToRemove = Array.from(cubeElement.classList).filter(className => className.includes('method_'));
            cubeElement.classList.remove(...classesToRemove);

            cubeElement.classList.add('method_'+value);

        }

    }



    console.log(`prepare_data Class: ${cid}, Value: ${value} `,cube);

}



isoBlock.addEventListener('click', function(event) {

    if (event.target.classList.contains('edit_cube')) {

        var dataId = event.target.getAttribute('data-id');

        let inputs = document.querySelectorAll('input.edit_sheme_input');
        inputs.forEach(input => {
            input.value = '';
        });


        var foundData = getCubeDataById(dataId);

        if (foundData) {

            const innerMain = document.querySelector('.inner_main');

            function handleInputChange(event) {
                const { target } = event;
                const value = target.value;
                const className = target.classList[0];

                prepare_data(className, value);
            }

            const inputs = innerMain.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                input.addEventListener('change', handleInputChange);
                let  className = input.classList[0];
                let cid = className.substring(2);
                input.value = foundData[cid] !== undefined ? foundData[cid] : '';

            });




            var idElement = document.querySelector('.b_id');
            // var titleInput = document.querySelector('.b_title');
            // var descTextarea = document.querySelector('.b_desc');
            // var tableInput = document.querySelector('.b_table');
            // var typeInput = document.querySelector('.b_type');
            // var colorInput = document.querySelector('.b_color');
            // var methodInput = document.querySelector('.b_method');

            if(foundData.requests)
            {
                add_request_data(foundData.requests);
            }


             idElement.textContent = foundData.id !== undefined ? foundData.id : '';
            // titleInput.value = foundData.title !== undefined ? foundData.title : '';
            // descTextarea.value = foundData.desc !== undefined ? foundData.desc : '';
            // tableInput.value = foundData.table !== undefined ? foundData.table : '';
            // typeInput.value = foundData.type !== undefined ? foundData.type : '';
            // colorInput.value = foundData.color !== undefined ? foundData.color : '';
            // methodInput.value = foundData.method !== undefined ? foundData.method : '';
        }
    }
    else if (event.target.classList.contains('open_btn')) {
        let dataId = event.target.getAttribute('data-id');

        var foundData = getCubeDataById(dataId);

        let datavalue  = foundData.table;
        let dop_request=prepare_request(foundData);

        let last_update  = foundData.last_update;
        if (last_update)
        {
            dop_request=dop_request+'&custom_date_row='+last_update;
        }
    // console.log(foundData);



        var popup = document.getElementById("popup");
        popup.style.display = "block";
        popup.style.top = "50%";
        popup.style.left = "50%";
        popup.style.transform = "translate(-50%, -50%)";

        let link = '/analysis/data.php?onlytable='+datavalue+dop_request;
        console.log(link);

        let pcntn='<iframe src="'+link+'"></iframe>';
        document.querySelector('.popup-content').innerHTML=pcntn;


    }


});
//////update cube






function save_request(dataType,  value) {
    let idElement = document.querySelector('.b_id');
    var id = parseInt(idElement.textContent);
    var cube = findCubeById(id);
    if (cube) {
       // cube.desc = descTextarea.value;


        if (!cube.requests)cube.requests ={};

        if (!value)
        {
            delete cube.requests[dataType];
        }
        else
        {
            cube.requests[dataType]=value;
        }


        let imsg =  inner_message(id,cube);
        document.querySelector('.cube#cube_'+id+' .cube_desc_message').innerHTML=imsg;
        show_charts(id);
        console.log(cube);
        ///  document.querySelector('.cube#cube_'+id+' .cube_desc_message').innerHTML=imsg;e;
    }
    ///console.log(`dataType: ${dataType}, dataValue: ${dataValue}, value: ${value}`);

}

var inputs = document.querySelectorAll('.edit_sheme_input');
inputs.forEach(input => {
    input.addEventListener('change', function() {
        const dataType = input.getAttribute('data-type');

        const value = input.value;
        save_request(dataType,  value);
    });
});





function findCubeById(id) {
    return object_array.cube.find(function(cube) {
        return cube.id === id;
    });
}




//////update cube
isoBlock.addEventListener('dblclick', function(event) {


    if (isButtonPressed) {


        let cubeElement = event.target.closest('.cube');

        if (cubeElement) {
            var cubeId = cubeElement.id;
            let  clickedCubeId = parseInt(cubeId.split('_')[1]);

            if (clickedCubeId !== -1) {

                removeItemById(clickedCubeId,'cube');
            }
            cubeElement.remove();
        }
        else {

            var offsetX = Math.floor((event.offsetX) / 100) * 100;
            var offsetY = Math.floor((event.offsetY) / 100) * 100;

            if (offsetX > 0 && offsetY > 0) {
                add_block(offsetX, offsetY);
            }

        }
    }
    else if (isButtonMoveBPressed) {

        let cubeElement = event.target.closest('.cube');

        if (cubeElement) {
            move_cube_id=0;
            var cubeId = cubeElement.id;
            let  clickedCubeId = parseInt(cubeId.split('_')[1]);

            if (clickedCubeId !== -1) {

                move_cube_id =clickedCubeId;
            }
            cubeElement.classList.add('transparent');
        }
        else {

            var offsetX = Math.floor((event.offsetX) / 100) * 100;
            var offsetY = Math.floor((event.offsetY) / 100) * 100;

            if (offsetX > 0 && offsetY > 0 && move_cube_id>0) {


                move_block(offsetX, offsetY,move_cube_id);
                move_cube_id=0;
            }

        }

    }


    if (isButtonPressed_line) {
        let cubeElement = event.target.closest('.line_point');

        if (cubeElement) {
            var cubeId = cubeElement.id;
            let  clickedCubeId = parseInt(cubeId.split('_')[1]);

            if (clickedCubeId !== -1) {

                removeItemById(clickedCubeId,'line_point');

            }
            cubeElement.remove();
        } else {

            var offsetX = Math.floor((event.offsetX) / 100) * 100;
            var offsetY = Math.floor((event.offsetY) / 100) * 100;

            if (offsetX > 0 && offsetY > 0) {
                add_block_line(offsetX, offsetY);
            }

        }
    }
});


isoBlock.addEventListener('mousedown', function (event) {
    if (event.button === 2) { // Проверяем, что это правая кнопка мыши
        isRightMouseDown = true;
        initialMouseX = event.clientX;
        initialMouseY = event.clientY;

        deltaX_last = 0;
        deltaY_last = 0;


        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', function () {
            isRightMouseDown = false;
            document.removeEventListener('mousemove', handleMouseMove);
        });
        event.preventDefault(); // Предотвращаем стандартное контекстное меню
    }
});


function handleMouseMove(event) {
    if (isRightMouseDown) {
        let deltaX = event.clientX - initialMouseX;
        let deltaY = event.clientY - initialMouseY;


        let delta_x_c = Number(deltaX - deltaX_last) / 10;
        let delta_y_c = Number(deltaY - deltaY_last) / 50;


        deltaX_last = deltaX;
        deltaY_last = deltaY;


        let newRotationZ = Number(initialRotationZ) + delta_x_c;
        let newRotationX = Number(initialRotationX) + delta_y_c;

        if (newRotationX > 80) newRotationX = 80;
        if (newRotationX < 0) newRotationX = 0;


        if (newRotationZ > 0) newRotationZ = 0;
        if (newRotationZ < -90) newRotationZ = -90;


        initialRotationZ = newRotationZ;
        initialRotationX = newRotationX;

        //  console.log(initialRotationX,initialRotationZ);

        isoBlock.style.transform = `rotateX(${newRotationX}deg) rotateY(0deg) rotateZ(${newRotationZ}deg) translateX(30%) translateY(${(-30 - newRotationZ/3)}%)`;
        let msg_Block = document.querySelectorAll('.cube_desc_message');
        msg_Block.forEach(element => {
            element.style.transform = `rotate3d(0, 0, 1, ${-newRotationZ}deg) rotate3d(1, 0, 0, ${-newRotationX}deg)`;
        });


    }
}


document.addEventListener('contextmenu', function (event) {
    event.preventDefault();
});


if (object_array)
{
    ////add_block
    if (object_array.cube)
    {
        if (object_array.cube[0])
        {
            object_array.cube.forEach(function(inner_data) {
                insert_block_to_field(inner_data.id,inner_data.x,inner_data.y,inner_data);
            });
        }
    }
    if (object_array.line_point)
    {
        if (object_array.line_point[0])
        {
            object_array.line_point.forEach(function(inner_data) {

                let tw = false;
                if (inner_data.tw)
                {
                    tw= inner_data.tw;
                }

                insert_line_to_field(inner_data.id,inner_data.parent,inner_data.x,inner_data.y,tw);

            });
        }
    }

}

document.body.addEventListener('click', function(event) {
    if (event.target.classList.contains('hide_left_sidebar')) {

        document.querySelector('body').classList.toggle('hidden_left');


    }
});


/*popup*/


var closeBtn = document.getElementById("closeBtn");

closeBtn.onclick = function () {
    popup.style.display = "none";
};


var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
popup.querySelector(".popup-header").onmousedown = dragMouseDown;

function dragMouseDown(e) {
    e = e || window.event;
    e.preventDefault();
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;
    document.onmousemove = elementDrag;
}

function elementDrag(e) {
    e = e || window.event;
    e.preventDefault();
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;
    var newTop = popup.offsetTop - pos2;
    var newLeft = popup.offsetLeft - pos1;
    // Проверяем, чтобы окно не выходило за пределы экрана
    if (newTop > 0 && newTop + popup.offsetHeight < window.innerHeight) {
        popup.style.top = newTop + "px";
    }
    if (newLeft > 0 && newLeft + popup.offsetWidth < window.innerWidth) {
        popup.style.left = newLeft + "px";
    }
}

function closeDragElement() {
    document.onmouseup = null;
    document.onmousemove = null;
}

/*popup*/



