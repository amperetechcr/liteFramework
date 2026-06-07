# Puente OpenCode ↔ OpenClaw

Este skill permite usar OpenClaw desde OpenCode para automatizar pruebas.

## Comandos de integracion

### Probar un modulo con OpenClaw

```bash
openclaw agent --agent main --message "Abre http://localhost/liteFramework/{ruta} y verificame que cargue sin errores"
```

### Ejecutar tests completos con OpenClaw

```bash
openclaw agent --agent main --message "Ejecuta los tests de liteFramework con PHPUnit y dime el resultado"
```

### Prueba UI de todos los modulos

```bash
openclaw agent --agent main --message "Prueba estos modulos de liteFramework: 1) /liteFramework/panelControl 2) /liteFramework/operadores 3) /liteFramework/configuracion 4) /liteFramework/apariencia 5) /liteFramework/generador-modulo 6) /liteFramework/generador-proyecto 7) /liteFramework/migraciones. Reporta si hay errores."
```

### Prueba de rendimiento

```bash
openclaw agent --agent main --message "Mide tiempos de carga de /liteFramework/ingreso, /liteFramework/panelControl, /liteFramework/operadores usando el browser tool"
```

## Flujo tipico

1. **Desarrollo**: `opencode run "Agregar campo telefono al formulario de operadores"`
2. **Verificacion**: `opencode run "Ejecuta los tests unitarios"`
3. **Tests E2E**: Ejecutar comando OpenClaw desde aca
4. **UI check**: Probar modulo con browser tool
