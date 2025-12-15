# Configuracion Final - Amazon SaaS

## Datos del Servidor
- **Dominio API**: `api.wandori.us`
- **Sistema**: Linux + WordPress + Glory
- **Proxy**: DataImpulse (5 GB comprados)

## Modelo de Negocio
- **Precio**: $20/mes por cliente
- **GB incluidos**: 4 GB por cliente
- **Trial**: 30 dias gratis
- **Cache**: Compartida entre clientes

## Reglas de GB
- Si excede: Bloquear temporalmente
- Futuro: Opcion de comprar GB extra
- Control anti-abuso: Rate limiting

## Proteccion contra Abuso
- Limite de requests por minuto
- Deteccion de patrones anomalos
- Alerta si consumo excesivo
