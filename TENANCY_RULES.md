# Reglas de Tenancy - Sistema Multi-Empresa

Este documento describe las reglas de aislamiento multi-empresa implementadas en el sistema.

## Arquitectura

- **Single Database**: Una sola base de datos compartida
- **Company Isolation**: Aislamiento por `company_id`
- **Route-based Tenancy**: Empresa determinada por parámetro de ruta `/c/{company}`
- **Default Company**: Si no se especifica empresa, se usa `'default'`

## Modelos con Tenancy

Los siguientes modelos implementan aislamiento por empresa:
- `User`
- `Employee` 
- `ShiftType`
- `ShiftConfiguration`
- `Shift`
- `PayPeriod`

## Componentes Clave

### 1. BelongsToCompany Trait
- Aplica automáticamente el `CompanyScope` global
- Asigna `company_id` automáticamente al crear registros
- Añade relación `belongsTo(Company::class)`

### 2. CompanyScope
- Filtra automáticamente todas las consultas por `company_id`
- Solo se aplica cuando `app('currentCompanyId')` está definido
- No afecta consultas de consola/seeds si no hay empresa actual

### 3. SetCurrentCompany Middleware
- Resuelve empresa desde parámetro de ruta `{company}`
- Almacena `company_id` en `app()->instance('currentCompanyId', $id)`
- Fallback a empresa 'default' si no encuentra la empresa

## Validaciones de Unicidad

### Por Empresa
- `users.email`: Único por empresa
- `employees.email`: Único por empresa  
- `shift_types.name`: Único por empresa
- `pay_periods.start_date + end_date`: Único por empresa

### Validación de Referencias Cruzadas
- `replacement_id` en shifts debe pertenecer a la misma empresa

## Autenticación

El login filtra por empresa actual:
```php
$credentials['company_id'] = app('currentCompanyId');
Auth::attempt($credentials);
```

## Uso en Rutas

### Con Empresa Específica
```
/c/company-a/api/employees  # Solo empleados de company-a
/c/company-b/api/shifts     # Solo turnos de company-b
```

### Sin Empresa (usa default)
```
/api/employees              # Solo empleados de empresa default
/api/login                  # Login a empresa default
```

## Testing

Ver `tests/Feature/TenancyIsolationTest.php` para ejemplos de:
- Aislamiento de datos entre empresas
- Validación de unicidad por empresa
- Validación de replacement_id entre empresas
- Scope automático funcionando correctamente

## Consideraciones Importantes

1. **Consola/Seeds**: El scope no se aplica en comandos de consola si no hay `currentCompanyId`
2. **Policies**: Implementar verificación de `auth()->user()->company_id === $model->company_id`
3. **Factories**: Actualizar para incluir `company_id`
4. **APIs Existentes**: Mantener compatibilidad usando empresa 'default'