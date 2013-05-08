<?php
 $image = 'img8.jpg';
?>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <table width="98%" align="center">
            <tr>
                <th width="50%">Original</th>
                <th width="50%">Grayscale</th>
            </tr>
            <tr>
                <td>
                    <img src="./manipulate?image=<?= $image ?>" width="100%" />
                </td>
                <td>
                    <img src="./manipulate?image=<?= $image ?>&grayscale" width="100%" />
                </td>
            </tr>
            <tr>
                <th colspan="2">Gausian</th>
            </tr>
            <tr>
                <td>
                    <img src="./manipulate?image=<?= $image ?>&gaussian_blur" width="100%" />
                </td>
                <td>
                    <img src="./manipulate?image=<?= $image ?>&grayscale&gaussian_blur" width="100%" />
                </td>
            </tr>
            <tr>
                <th colspan="2">Sobel</th>
            </tr>
            <tr>
                <td>
                    <img src="./manipulate?image=<?= $image ?>&gaussian_blur&sobel" width="100%" />
                </td>
                <td>
                    <img src="./manipulate?image=<?= $image ?>&grayscale&gaussian_blur&sobel" width="100%" />
                </td>
            </tr>
        </table>
    </body>
</html>
