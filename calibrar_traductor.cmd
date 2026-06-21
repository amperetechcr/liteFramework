@echo off
REM Calibracion automatica del Traductor de Prompts
REM Programar en Windows Task Scheduler para ejecucion diaria
REM Ejemplo: diario a las 6:00 AM

"C:\xampp\php\php.exe" "C:\xampp\htdocs\liteFramework\servidor\consola\traductor_calibrar.php" --modo=completo
