# 🚀 Guía de Instalación - Mejoras de Colaboraciones

## Paso 1: Aplicar migraciones

Las migraciones ya se han ejecutado. Puedes verificar con:

```bash
# Dentro del contenedor
docker compose exec php php artisan migrate:status

# O si ejecutas desde fuera
docker compose exec php php artisan migrate
```

## Paso 2: Verificar rutas

Las rutas ya están registradas. Puedes listar todas:

```bash
docker compose exec php php artisan route:list --name=openwa
```

Deberías ver:
```
GET|HEAD        openwa/api/search-trabajadores         openwa.collab.search-trabajadores
GET|HEAD        openwa/colaboraciones                  openwa.collab.index
POST            openwa/colaboraciones/create-group     openwa.collab.create.group
POST            openwa/colaboraciones/send-grupo       openwa.collab.send.group
POST            openwa/colaboraciones/send-phone       openwa.collab.send.phone
POST            openwa/colaboraciones/send-trabajador  openwa.collab.send.user
```

## Paso 3: Acceder a la interfaz

```
http://tu-dominio/openwa/colaboraciones
```

## Paso 4: Crear tu primer grupo

1. **Crea un grupo en OpenWA** con los trabajadores deseados
2. **Obtén el chat_id del grupo** (formato: `120363***@g.us`)
3. **En la interfaz**, ve a "👥 Enviar a Grupo" → crea el grupo con:
   - Nombre: "Mi grupo de trabajo"
   - Chat ID: `120363***@g.us`
4. ¡Listo! Ya puedes enviar mensajes al grupo

## 📋 Checklist de verificación

- [ ] Las migraciones se ejecutaron correctamente
  ```bash
  docker compose exec php php artisan tinker
  >>> \App\Models\WhatsappGroup::count()
  # Debería devolver 0 o el número actual
  ```

- [ ] Los modelos están accesibles
  ```bash
  docker compose exec php php artisan tinker
  >>> app(\App\Models\WhatsappGroup::class)
  # No debe dar error
  ```

- [ ] La vista se carga sin errores
  - Abre: `http://tu-dominio/openwa/colaboraciones`
  - Busca cualquier error en la consola (F12)

- [ ] La API de búsqueda funciona
  - Abre: `http://tu-dominio/openwa/api/search-trabajadores?q=alejandro`
  - Debería devolver JSON con trabajadores

## 🔧 Solución de problemas

### Error: "Table 'whatsapp_groups' doesn't exist"
```bash
# Ejecuta las migraciones
docker compose exec php php artisan migrate
```

### Error: "Class not found"
```bash
# Regenera el autoloader de Composer
docker compose exec php composer dump-autoload
docker compose exec php php artisan cache:clear
```

### La búsqueda de trabajadores no funciona
```bash
# Verifica la tabla de trabajadores
docker compose exec php php artisan tinker
>>> \App\Models\TrabajadorPolifonia::count()
# Debería devolver > 0
```

### Los mensajes no se envían
Revisa el log:
```bash
docker compose exec php tail -f storage/logs/openwa.log
```

## 📝 Configuración opcional

### Personalizar max de resultados en búsqueda

En `OpenWACollaborationController.php`, línea ~100:
```php
->limit(10)  // Cambiar a 20 para más resultados
```

### Cambiar tiempo de retención de mensajes

En `resources/views/openwa/colaboraciones.blade.php`, línea ~165:
```blade
->limit(20)  // Cambiar a 50 para mostrar más mensajes
```

## 🎓 Ejemplos de uso

### Ejemplo 1: Notificar a un equipo
```
1. Buscar: "Carlos García"
2. Mensaje: "El proyecto XYZ está disponible"
3. Enviar
```

### Ejemplo 2: Enviar a grupo de soporte
```
1. Seleccionar grupo: "Equipo de Soporte (5 miembros)"
2. Mensaje: "Nueva política de respuesta registrada"
3. Enviar
```

### Ejemplo 3: Envío urgente por teléfono
```
1. Teléfono: "34622435165"
2. Mensaje: "Llamada urgente disponible"
3. Enviar rápidamente
```

## 🔐 Notas de seguridad

- ✅ Solo usuarios autenticados pueden acceder
- ✅ Todos los inputs se validan y sanitizan
- ✅ Los chat_ids se validan como únicos
- ✅ Los mensajes están limitados a 2000 caracteres

## 📞 Contacto / Soporte

Si necesitas ayuda:
1. Revisa el archivo `COLABORACIONES_MEJORAS.md`
2. Consulta los logs: `storage/logs/openwa.log`
3. Verifica la BD con: `docker compose exec db mysql -u root -p`

---

**Setup completado ✅**  
**Versión:** 1.0.0  
**Fecha de instalación:** 21/05/2026

