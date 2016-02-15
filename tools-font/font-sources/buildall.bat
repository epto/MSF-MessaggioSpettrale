@echo off
mkdir img
mkdir tpl
for %%i in (*.font.txt) do php ..\font-edit.php --update %%i -o * -N
for %%i in (*.font) do php ..\font2img.php -i %%i -o img\*.png -n -N --mktpl tpl\*.tpl.ini
move /Y *.font ..\..\fonts > nul
move /Y img\*.* ..\font-images\img > nul
move /Y tpl\*.* ..\font-images\tpl > nul
rmdir img
rmdir tpl