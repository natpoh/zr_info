var wp_ajax = '/wp-admin/admin-ajax.php';
jQuery(function ($) {
    $(document).ready(function () {
        const selectedTags = new Set();  // Множество для хранения выбранных тегов
        $('#tag-list li').each(function(){
            let tag = $(this).text();
            console.log(tag);
            selectedTags.add(tag); 
        });
        console.log(selectedTags);
        // Отображение подсказок при вводе текста
        $('#tag-input').on('input', function () {
            const query = $(this).val().toLowerCase().split(',').pop().trim(); // Последнее слово для автокомплита
            $('#suggestions').empty();  // Очистка предыдущих подсказок

            if (query) {
                const filteredTags = all_tags.filter(tag => tag.toLowerCase().includes(query) && !selectedTags.has(tag));
                $.each(filteredTags, function (index, tag) {
                    const suggestionItem = $('<li>').text(tag);
                    suggestionItem.on('click', function (e) {
                        e.preventDefault();
                        addTag(tag);
                    });
                    $('#suggestions').append(suggestionItem);
                });
            }
        });

        // Функция добавления одного или нескольких тегов
        function addTagsFromInput(inputValue) {
            const tagArray = inputValue.split(',').map(tag => tag.trim()).filter(tag => tag);  // Разделение на теги

            tagArray.forEach(tag => {
                addTag(tag);  // Добавление каждого тега
            });
        }

        // Функция добавления тега
        function addTag(tag) {
            if (!selectedTags.has(tag)) {
                selectedTags.add(tag);  // Добавление тега в список выбранных

                const tagItem = $('<li>').text(tag);

                $('#tag-list').append(tagItem);
                $('#tag-input').val('');  // Очистка поля ввода
                $('#suggestions').empty();  // Очистка подсказок

                // Вызов функции отправки AJAX-запроса
                sendTagsToServer();
            }
        }

        $('#tag-list').on('click','li', function (e) {
            e.preventDefault();
            let name = $(this).text();
            removeTag(name);  // Удаление тега при нажатии на крестик
        });

        // Функция для удаления тега
        function removeTag(tag) {
            selectedTags.delete(tag);
            $(`#tag-list li:contains(${tag})`).remove();

            // Вызов функции отправки AJAX-запроса для обновления
            sendTagsToServer();
        }

        // Добавление тегов при нажатии Enter
        $('#tag-input').on('keydown', function (e) {
            if (e.key === 'Enter' && $(this).val().trim()) {
                addTagsFromInput($(this).val().trim());
                e.preventDefault();  // Предотвращение отправки формы
            }
        });

        // Добавление тегов при нажатии кнопки "Добавить"
        $('#add-tag-button').on('click', function (e) {
            e.preventDefault();
            const inputValue = $('#tag-input').val().trim();
            if (inputValue) {
                addTagsFromInput(inputValue);
            }
        });

        // Функция отправки тегов на сервер (заглушка)
        function sendTagsToServer() {
            console.log("Отправка на сервер: ", Array.from(selectedTags));

            $.ajax({
                type: 'GET',
                dataType: "json",
                url: wp_ajax,
                data: {
                    "action": "cm_add_tag",
                    "tags": Array.from(selectedTags),
                    "camp_id": $('#camp_id').val(),
                    "post_type": $('#camp_id').data('type'),
                },
                success: function (response) {
                    if (response.type == "ok") {

                    }
                }
            });
        }

        // Отображение популярных тегов
        function displayPopularTags() {
            $('#popular-tags li').on('click', function (e) {
                e.preventDefault();
                let name = $(this).text();
                addTag(name);
            });
        }

        // Инициализация отображения популярных тегов
        displayPopularTags();
    });

});


