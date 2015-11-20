<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <input type="submit" class="button" name="insert" value="insert" />
        <input type="submit" class="button" name="select" value="select" />
        <p id="result"></p>
        <script>
            var div = document.getElementById('result');
            $(document).ready(function () {
                $('.button').click(function () {
                    var clickBtnValue = $(this).val();
                    var ajaxurl = 'ajax.php',
                            data = {'action': clickBtnValue};
                    $.post(ajaxurl, data, function (response) {
                        // Response div goes here.
                        div.innerHTML = div.innerHTML + 'Extra stuff';
                        alert("action performed successfully");
                    });
                });

            });
            


        </script>

    </body>
</html>