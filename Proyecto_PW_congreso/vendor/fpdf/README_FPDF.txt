INSTRUCCIONES PARA ACTIVAR PDF CON FPDF
=========================================

El sistema ya genera recibos en HTML que funcionan perfectamente.
Si quieres que sean PDF en lugar de HTML, sigue estos pasos:

1. Ve a https://www.fpdf.org
2. Descarga la versión 1.86 (fpdf186.zip)
3. Descomprime el zip
4. Copia el archivo "fpdf.php" a esta misma carpeta:
   congreso1_2026/vendor/fpdf/fpdf.php

Listo. El sistema detecta automáticamente si fpdf.php existe
y genera PDF en lugar de HTML sin ningún otro cambio.

Los recibos quedan guardados en: uploads/recibos/
