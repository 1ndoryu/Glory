<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * API Provider Interface - Contrato para proveedores de API de Amazon.
 * 
 * ARCH-01: Esta interface permite cambiar de proveedor de API sin modificar
 * la logica del plugin. Actualmente soporta RapidAPI, pero esta preparado
 * para Amazon PA-API oficial en el futuro.
 * 
 * Implementaciones:
 * - RapidApiProvider: amazon-data.p.rapidapi.com (actual)
 * - AmazonPaApiProvider: Amazon Product Advertising API (futuro)
 */
interface ApiProviderInterface
{
    /**
     * Busca productos por palabra clave.
     * 
     * @param string $keyword Palabra clave de busqueda
     * @param int $page Numero de pagina
     * @return array Lista de productos encontrados
     */
    public function searchProducts(string $keyword, int $page = 1): array;

    /**
     * Obtiene un producto por su ASIN.
     * 
     * @param string $asin Amazon Standard Identification Number
     * @return array Datos del producto o array vacio si no existe
     */
    public function getProductByAsin(string $asin): array;

    /**
     * Obtiene ofertas/deals actuales.
     * 
     * @param int $page Numero de pagina
     * @return array Lista de ofertas
     */
    public function getDeals(int $page = 1): array;

    /**
     * Obtiene el nombre del proveedor para mostrar en admin.
     * 
     * @return string Nombre legible del proveedor
     */
    public function getProviderName(): string;

    /**
     * Verifica si el proveedor esta configurado correctamente.
     * 
     * @return bool True si las credenciales estan configuradas
     */
    public function isConfigured(): bool;

    /**
     * Obtiene el dominio de Amazon para la region actual.
     * 
     * @return string Dominio (ej: amazon.com, amazon.es)
     */
    public function getDomain(): string;
}
