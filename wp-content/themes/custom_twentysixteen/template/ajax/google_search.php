<?php


?>
<div class="maincontent">
<script async src="https://cse.google.com/cse.js?cx=83ba3cb765c2b4759"></script>
<div class="gcse-search">
    <script>
        function executeSearch() {
            var query = "Ваш поисковый запрос"; // Здесь укажите свой поисковый запрос
            var element = document.getElementById('search-box');
            if (element) {
                element.value = query;
                var searchForm = document.getElementById('gsc-search-form');
                if (searchForm) {
                    searchForm.submit();
                }
            }
        }



        document.addEventListener('DOMContentLoaded', function() {
            var query = "terminator 2"; // Здесь укажите свой поисковый запрос
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes) {
                        mutation.addedNodes.forEach(function(addedNode) {

                            console.log(addedNode);
                            if (addedNode.classList && addedNode.classList.contains('gsc-input')) {
                                addedNode.value = query;
                               document.querySelector('button.gsc-search-button').click();
                               observer.disconnect();

                            }

                        });
                    }
                });
            });

            var targetNode = document.querySelector('.maincontent');
            if (targetNode) {
                observer.observe(targetNode, { childList: true, subtree: true });
            }
        });

    </script>
</div>
</div>