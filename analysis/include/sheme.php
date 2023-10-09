<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';



class Sheme
{

public static function table()
{
    !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

    $array_rows = array(
        'id'=>array('w'=>5),
    );

    Crowdsource::Show_admin_table('option_sheme',$array_rows,1,'option_sheme','',1,1,1,0);

}

public static function edit_data($id)
{

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = file_get_contents("php://input");

        $q ="UPDATE `option_sheme` SET `data` = ? WHERE `id`=".intval($id);
        Pdo_an::db_results_array($q,[$data]);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'option_sheme', array('id' => intval($id)), 'option_sheme',6);

        header('Content-Type: application/json');
        echo json_encode(['message' => ' ok']);
    }

}

private static function get_from_db($id)
{
$q="SELECT * FROM `option_sheme` WHERE `id` = ".$id." limit 1";
$r = Pdo_an::db_results_array($q);
if ($r)
{
    return $r[0];
}


}

public static function run($id)
{

    $id=intval($id);

    $data = self::get_from_db($id);


    self::front($data);
    self::script($data);
    self::styles();
}
public static function front($data)
{
    ///get arrays

    $name = $data['name'];
    $id = $data['id'];



?>

<div class="popup" id="popup">
    <div class="popup-header">
        <span class="close" id="closeBtn">&times;</span>
    </div>
    <div class="popup-content">

    </div>
</div>


<div class="left_menu">
<div class="menu_title"><?php echo $name; ?></div>
    <button class="add-button add_cube">Add Cube</button>
    <button class="add-button move_cube">Move Cube</button>
    <button class="add-button add-line-button">Add Line</button>

    <div class="lines_container">
    <div class="menu_lines"></div>
    <div style="display: flex; gap:2px">

    <button class="add-button add-new-line-button">Add New Line</button>
    <button class="add-button delete-new-line-button">Delete</button>
    </div>
    </div>


    <div class="block_edit_menu">
        <p class="menu_title">Edit block</p>
        <div class="b_row">id<span class="b_id"></span></div>
        <div class="b_row">title <input class="b_title" ></div>
        <div class="b_row">desc <textarea class="b_desc" ></textarea></div>
        <div class="b_row">table <input class="b_table" ></div>
    </div>



    <button class="add-button save_button">Save data</button><span class="save_result"></span>




</div>
<div class="main_scroll">
    <div class="main_win">


        <div class="iso">

<!--            <div class="cube">-->
<!--                <div class="face front"></div>-->
<!--                <div class="face right"></div>-->
<!--                <div class="face left"></div>-->
<!--                <div class="face top"></div>-->
<!--                <div class="face bottom"></div>-->
<!--                <div class="cube_desc">-->
<!--                    <div class="cube_desc_message">test text-->
<!---->
<!--                        <button id="openBtn">open</button>-->
<!---->
<!---->
<!--                        <div id="chart-container" style="width: 100px; height: 100px;"></div>-->
<!---->
<!--                    </div>-->
<!--                </div>-->

            </div>


        </div>
</div>


    <?php


    }


    public static function script($data)
    {

        $object = $data['data'];
        $id = $data['id'];

        ?>


        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script type="text/javascript">

            var main_id =<?php echo $id ; ?>;

            <?php if ($object) {

                ?>
            var object_array = <?php echo $object ; ?>;

            <?php

            }
            else{
                ?>



            var object_array = {};
            object_array.cube = [];
            object_array.line_point = [];
            object_array.line= [];

            <?php
            }
            ?>



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

            function insert_block_to_field(id,x,y,inner_data=[])
            {

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
                        inner_message+='<p class="mb_table"><button data-value="'+inner_data.table+'" class="open_btn">'+inner_data.table+'</button></p>';
                    }

                }

                inner_message+='<p class="mb_edit">id:'+id+' <button data-id="'+id+'" class="edit_cube">Edit</button></p>';


                let block_html =`<div class="cube" id="cube_${id}" style="left: ${x}px; top: ${y}px;">
                <div class="face front"></div>
                <div class="face right"></div>
                <div class="face left"></div>
                <div class="face top"></div>
                <div class="face bottom"></div>
                <div class="cube_desc">
                    <div class="cube_desc_message">${inner_message}</div>
                </div>
            </div>`;


                var isoBlock = document.querySelector('.iso');
                isoBlock.insertAdjacentHTML('beforeend', block_html);

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

                               insert_line_to_field(inner_data.id,inner_data.parent,inner_data.x,inner_data.y);
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


            function insert_line_to_field(id,parent,x,y)
            {

                let block_html =`<div class="line_point line" id="line_${id}" style="left: ${x}px; top: ${y}px;"></div>`;
                var isoBlock = document.querySelector('.iso');
                isoBlock.insertAdjacentHTML('beforeend', block_html);
                //draw lines
                draw_lines(id,parent);


            }




            function add_block_line(x,y){
                let [id,parent]  = addLine(x,y);
                insert_line_to_field(id,parent,x,y);

            }






            const mainwin = document.querySelector('.main_win');
            let scale = 1; // начальный масштаб
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

                fetch('/analysis/include/sheme.php?edit_data='+main_id, {
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
                    })
                    .catch(function(error) {
                        console.error('There has been a problem with your fetch operation:', error);
                    });

            });




            const addButton = document.querySelector('.add_cube');

            let isButtonPressed = false;

            addButton.addEventListener('click', function() {
                isButtonPressed = !isButtonPressed;
                document.querySelector('.add-button').classList.remove('active');
                if (isButtonPressed) {
                    addButton.classList.add('active');
                    document.querySelector('body').classList.add('add_block');
                } else {
                    addButton.classList.remove('active');
                    document.querySelector('body').classList.remove('add_block');
                }
            });



            const moveButton = document.querySelector('.move_cube');

            let isButtonMoveBPressed = false;
            var move_cube_id =0;

            moveButton.addEventListener('click', function() {
                isButtonMoveBPressed = !isButtonMoveBPressed;
                 document.querySelector('.add-button').classList.remove('active');



                if (isButtonMoveBPressed) {
                    moveButton.classList.add('active');
                    document.querySelector('body').classList.add('move_block');
                } else {
                    moveButton.classList.remove('active');
                    document.querySelector('body').classList.remove('move_block');
                }
            });

            const add_line_button = document.querySelector('.add-line-button');

            let isButtonPressed_line = false;







            function check_click_linebutton()
            {
                // Находим все кнопки в меню menu_lines
                var menuButtons = document.querySelectorAll('.menu_lines button');

// Функция для установки класса active на нажатой кнопке и снятия класса с остальных
                function setActiveButton(clickedButton) {
                    menuButtons.forEach(function(button) {
                        button.classList.remove('active');
                    });
                    clickedButton.classList.add('active');
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



            add_line_button.addEventListener('click', function() {
                isButtonPressed_line = !isButtonPressed_line;
                document.querySelector('.add-button').classList.remove('active');
                if (isButtonPressed_line) {
                    add_line_button.classList.add('active');
                    document.querySelector('body').classList.add('add_line');

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

            isoBlock.addEventListener('click', function(event) {

                if (event.target.classList.contains('edit_cube')) {

                    var dataId = event.target.getAttribute('data-id');



                    var foundData = getCubeDataById(dataId);

                    if (foundData) {
                        var idElement = document.querySelector('.b_id');
                        var titleInput = document.querySelector('.b_title');
                        var descTextarea = document.querySelector('.b_desc');
                        var tableInput = document.querySelector('.b_table');

                        idElement.textContent = foundData.id !== undefined ? foundData.id : '';
                        titleInput.value = foundData.title !== undefined ? foundData.title : '';
                        descTextarea.value = foundData.desc !== undefined ? foundData.desc : '';
                        tableInput.value = foundData.table !== undefined ? foundData.table : '';
                    }
                }
                else if (event.target.classList.contains('open_btn')) {
                    var datavalue = event.target.getAttribute('data-value');

                    var popup = document.getElementById("popup");
                        popup.style.display = "block";
                        popup.style.top = "50%";
                        popup.style.left = "50%";
                        popup.style.transform = "translate(-50%, -50%)";

                        let pcntn='<iframe src="/analysis/data.php?onlytable='+datavalue+'"></iframe>';
                    document.querySelector('.popup-content').innerHTML=pcntn;


                }


            });
            //////update cube


            var titleInput = document.querySelector('.b_title');
            var descTextarea = document.querySelector('.b_desc');
            var tableInput = document.querySelector('.b_table');


            function findCubeById(id) {
                return object_array.cube.find(function(cube) {
                    return cube.id === id;
                });
            }

            titleInput.addEventListener('input', function() {
                let idElement = document.querySelector('.b_id');
                var id = parseInt(idElement.textContent);
                var cube = findCubeById(id);
                if (cube) {
                    cube.title = titleInput.value;

                }
            });


            descTextarea.addEventListener('input', function() {
                let idElement = document.querySelector('.b_id');
                var id = parseInt(idElement.textContent);
                var cube = findCubeById(id);
                if (cube) {
                    cube.desc = descTextarea.value;

                }
            });

            tableInput.addEventListener('input', function() {
                let idElement = document.querySelector('.b_id');
                var id = parseInt(idElement.textContent);
                var cube = findCubeById(id);
                if (cube) {
                    cube.table = tableInput.value;

                }
            });

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

                    isoBlock.style.transform = `rotateX(${newRotationX}deg) rotateY(0deg) rotateZ(${newRotationZ}deg)`;
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

                            insert_line_to_field(inner_data.id,inner_data.parent,inner_data.x,inner_data.y);

                        });
                    }
                }

            }




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



            // Highcharts



            function chart() {

                // Генерируем случайные данные для графика
                var data = [];
                for (var i = 0; i < 30; i++) {
                    data.push(Math.floor(Math.random() * 100)); // Замените это на ваши данные
                }
                Highcharts.chart('chart-container', {
                    chart: {
                        // plotBackgroundColor: null,
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
                            borderWidth: 0
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    xAxis: {
                        // showFirstLabel: false,
                        visible: false,
                    },
                    yAxis: {
                        // title: {
                        //      text: ''
                        //  },
                        visible: false,
                        //gridLineWidth: 0,
                    },
                    series: [{
                        data: data
                    }]
                });
                // Highcharts
            }

        </script>
        <?php

    }
    public static function styles()
    {
        ?>
        <style type="text/css">
            :root {
                --cube_1x1_x: 100px;
                --cube_1x1_y: 100px;
                --cube_1x1_z: 100px;
            }

            body {
                font-size: 16px;
                padding: 0;
                margin: 0;
            }

            .popup-content iframe {
                width: 85vw;
                height: 90vh;
                border: none;
            }
            .line_point{
                width: 100px;
                height: 100px;
                background-color: #ccc;
                position: absolute;
            }

            .left_menu {
                position: fixed;
                background-color: #fff;
                width: 200px;
                height: 100vh;
                left: 0px;
                top: 0px;
                z-index: 1;
                border-right: 1px solid #ccc;
                overflow-y: auto;
            }


            .highcharts-background {
                fill: transparent;
            }

            .highcharts-credits {
                display: none;
            }

            .cube_desc {
                position: absolute;
                height: 100px;
                width: 100px;
                transform: translateZ(200px);
                left: 0;
                top: 0;
                transform-style: preserve-3d;
            }

            .cube_desc_message {
                transform-style: preserve-3d;
                position: absolute;
                color: #232323;
                font-size: 16px;
                background-color: rgba(255, 255, 255, 0.87);
                padding: 5px;
                border-radius: 20px;
                border: 1px solid #0000005c;
                transform: rotate3d(0, 0, 1, 45deg) rotate3d(1, 0, 0, -60deg);
                min-height: 100px;
                min-width: 100px;
            }

            .main_win {
                width: 100%;
                height: 100%;

            }

            .iso {
                transform: rotateX(60deg) rotateY(0deg) rotateZ(-45deg);
                transform-style: preserve-3d;
                position: absolute;
                height: 5000px;
                width: 5000px;
                text-align: center;
                margin: 0 auto;
                background: url(../images/grid.svg) 0px 0px;
                box-sizing: border-box;
                padding: 0;
                background-size: 100px;
                top: -1200px;
                left: -300px;
            }

            .main_scroll {
                overflow: scroll;
                height: 100vh;
                width: 100vw;
                position: fixed;
                left: 0;
                top: 0;
            }

            .cube {
                width: var(--cube_1x1_x);
                height: var(--cube_1x1_x);
                position: absolute;
                transform-style: preserve-3d;
                left: 2000px;
                top: 1000px;
                transform: translateX(0px) translateY(0px);
            }

            .cube.transparent .face {
                opacity: 0.5;
            }


           body.add_line .cube {
                pointer-events: none;

            }

            body.add_line  .cube .face {
               opacity: 0.5;
            }


            body.add_block .line_point {
                pointer-events: none;

            }

            .cube_2 {

                left: 2000px;
                top: 1700px;

            }


            .cube .face {
                position: absolute;
                width: var(--cube_1x1_x);
                height: var(--cube_1x1_x);
                background-color: #4CAF50;
                border: 1px solid #333;
            }

            .cube .front {
                background-color: #3a7c3d;
                transform: rotateY(0deg) translateZ(var(--cube_1x1_x));
            }

            .cube .right {
                background-color: #4CAF50;
                transform: rotateY(90deg) translateZ(calc(var(--cube_1x1_x) / 2)) translateX(calc(var(--cube_1x1_x) / -2));
            }

            .cube .left {
                background-color: #3cd343;
                transform: rotateY(-90deg) translateZ(calc(var(--cube_1x1_x) / 2)) translateX(calc(var(--cube_1x1_x) / 2));
            }

            .cube .top {
                background-color: #4CAF50;
                transform: rotateX(90deg) translateZ(calc(var(--cube_1x1_x) / 2)) translateY(calc(var(--cube_1x1_x) / 2));
            }

            .cube .bottom {
                background-color: #4CAF50;
                transform: rotateX(-90deg) translateZ(calc(var(--cube_1x1_x) / 2)) translateY(calc(var(--cube_1x1_x) / -2));
            }

            /*popup*/
            .popup {
                display: none;
                position: absolute;
                max-width: 90vw;
                border: 1px solid #ccc;
                background-color: #fff;
                z-index: 10;
                box-shadow: rgba(20, 20, 21, 0.84) 0px 0px 1px 2px, rgba(20, 20, 21, 0.84) 0px 0px 0px 1000vh;

            }

            .popup-header {
                background-color: #f1f1f1;
                padding: 10px;
                cursor: move;
            }

            .popup-content {
                padding: 20px;
            }

            .close {
                float: right;
                font-size: 24px;
                cursor: pointer;
            }

            /*popup*/



            .line {
                position: absolute;
                width: 30px;
                height: 26px;
                background: url(../images/arrow.svg) 0px 0px;
                transform-origin: left center;
                transform: rotate(0deg);
                margin-top: 37px;
                transform-origin: left center;
                transform: rotate(0deg);
                animation: moveBackground 15s linear infinite;
                border-radius: 12px;
                padding: 0px 3px;
                margin-left: 50px;
            }
            @keyframes moveBackground {
                0% {
                    background-position: 0px 0px;
                }
                100% {
                    background-position: 100% 0px;
                }
            }

.mb_title{
    font-size: 20px;
}
.mb_table{
    font-size: 12px;
}

          /*left menu*/
            .add-button {
                margin-bottom: 20px;
                padding: 10px 20px;
                background-color: #3498db;
                color: white;
                cursor: pointer;
                border: none;
            }

            .add-button.active {
                background-color: #b92971;
            }

            .lines_container{
                display: none;
            }
            body.add_line .lines_container{
                display: block;
            }
            .menu_lines {
                padding-left: 15px;
                max-height: 300px;
                overflow-y: auto;
                overflow-x: hidden;
                margin-bottom: 20px;
            }


            .menu_lines button {
                display: block;
                width: 180px;
                height: 30px;
                margin-bottom: 10px;
                background-color: #3498db;
                color: #fff;
                border: none;

                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .menu_lines button.active {
                background-color: #b92971;
            }

            .menu_title{

                font-size: 18px;
                text-align: center;
                padding: 8px;
            }

            .block_edit_menu{
                padding-bottom: 20px;


            }
            .block_edit_menu .b_row {
                display: flex;
                padding: 5px;
                gap: 5px;
                box-sizing: border-box;
                justify-content: space-between;
                align-items: center;
            }
            .block_edit_menu input,  .block_edit_menu textarea {
                max-width: 100%;
                width: 150px;
            }
        </style>

        <?php

    }


    }

    if (isset($_GET['edit_data']))
    {

        Sheme::edit_data($_GET['edit_data']);
    }
    else if (isset($_GET['edit_sheme']))
    {
        Sheme::run($_GET['edit_sheme']);

    }
    else
    {
        Sheme::table();

    }




    ?>


