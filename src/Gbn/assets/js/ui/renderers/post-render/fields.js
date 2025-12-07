(function (global) {
    'use strict';

    /**
     * POST RENDER - FIELDS MODULE
     *
     * Maneja la población de campos semánticos (PostFields) con datos de posts.
     * Incluye lógica para templates editables y clones.
     *
     * @module Gbn.ui.renderers.postRender.fields
     */

    var Gbn = (global.Gbn = global.Gbn || {});
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};
    Gbn.ui.renderers.postRenderModules = Gbn.ui.renderers.postRenderModules || {};

    /**
     * Puebla los campos de un clon con datos del post.
     * Para clones visuales (no editables).
     *
     * @param {HTMLElement} clone Elemento clon
     * @param {Object} post Datos del post
     */
    function populateCloneFields(clone, post) {
        var fields = clone.querySelectorAll('[gloryPostField], [glorypostfield]');

        fields.forEach(function (field) {
            var fieldType = field.getAttribute('gloryPostField') || field.getAttribute('glorypostfield');

            switch (fieldType) {
                case 'title':
                    var titleLink = field.querySelector('a');
                    if (titleLink) {
                        titleLink.href = post.link || '#';
                        titleLink.textContent = post.title || 'Título';
                    } else {
                        field.textContent = post.title || 'Título';
                    }
                    break;
                case 'excerpt':
                    field.textContent = post.excerpt || 'Extracto...';
                    break;
                case 'date':
                    field.textContent = post.date || 'Fecha';
                    break;
                case 'author':
                    field.textContent = post.author || 'Autor';
                    break;
                case 'featuredImage':
                    var img = field.querySelector('img');
                    if (img && post.featuredImage) {
                        img.src = post.featuredImage;
                        img.alt = post.title || '';
                    } else if (post.featuredImage) {
                        // Soporte para background-image (asBackground: true)
                        field.style.backgroundImage = 'url(' + post.featuredImage + ')';
                    }
                    break;
                case 'categories':
                    if (post.categories && post.categories.length > 0) {
                        field.textContent = post.categories
                            .map(function (c) {
                                return c.name;
                            })
                            .join(', ');
                    }
                    break;
                default:
                    // Campos meta:xxx
                    if (fieldType && fieldType.indexOf('meta:') === 0) {
                        var metaKey = fieldType.substring(5);
                        if (post.meta && post.meta[metaKey] !== undefined) {
                            field.textContent = post.meta[metaKey];
                        }
                    }
            }
        });
    }

    /**
     * Puebla el template original con datos de un post para preview.
     * Guarda los valores originales para poder restaurarlos antes de persistir.
     *
     * @param {HTMLElement} template Elemento template (PostItem)
     * @param {Object} post Datos del post para preview
     */
    function populateTemplateWithPreview(template, post) {
        var fields = template.querySelectorAll('[gloryPostField], [glorypostfield]');

        fields.forEach(function (field) {
            var fieldType = field.getAttribute('gloryPostField') || field.getAttribute('glorypostfield');

            // Guardar contenido original para restaurar antes de persistir
            if (!field.hasAttribute('data-gbn-original-content')) {
                field.setAttribute('data-gbn-original-content', field.innerHTML);
            }

            // Marcar como campo con preview
            field.setAttribute('data-gbn-preview-field', 'true');

            switch (fieldType) {
                case 'title':
                    var titleLink = field.querySelector('a');
                    if (titleLink) {
                        titleLink.href = post.link || '#';
                        titleLink.textContent = post.title || 'Título del post';
                    } else {
                        field.textContent = post.title || 'Título del post';
                    }
                    break;

                case 'excerpt':
                    field.textContent = post.excerpt || 'Extracto del post...';
                    break;

                case 'content':
                    field.innerHTML = post.content || '<p>Contenido del post...</p>';
                    break;

                case 'date':
                    field.textContent = post.date || 'Fecha';
                    break;

                case 'author':
                    field.textContent = post.author || 'Autor';
                    break;

                case 'featuredImage':
                    if (post.featuredImage) {
                        var img = field.querySelector('img');
                        if (img) {
                            img.src = post.featuredImage;
                            img.alt = post.title || '';
                        } else {
                            field.style.backgroundImage = 'url(' + post.featuredImage + ')';
                        }
                    }
                    break;

                case 'categories':
                    if (post.categories && post.categories.length > 0) {
                        field.textContent = post.categories
                            .map(function (c) {
                                return c.name;
                            })
                            .join(', ');
                    }
                    break;

                case 'tags':
                    if (post.tags && post.tags.length > 0) {
                        field.textContent = post.tags
                            .map(function (t) {
                                return t.name;
                            })
                            .join(', ');
                    }
                    break;

                default:
                    // Campos meta:xxx
                    if (fieldType && fieldType.indexOf('meta:') === 0) {
                        var metaKey = fieldType.substring(5);
                        if (post.meta && post.meta[metaKey] !== undefined) {
                            field.textContent = post.meta[metaKey];
                        } else {
                            field.textContent = '[' + fieldType + ']';
                        }
                    }
            }
        });
    }

    /**
     * Puebla los campos semánticos de un item con los datos del post.
     * Versión completa con todos los tipos de campo.
     *
     * @param {HTMLElement} item Elemento del item (clon del template)
     * @param {Object} post Datos del post
     */
    function populatePostFields(item, post) {
        var fields = item.querySelectorAll('[gloryPostField]');

        fields.forEach(function (field) {
            var fieldType = field.getAttribute('gloryPostField');

            switch (fieldType) {
                case 'title':
                    field.textContent = post.title || '';
                    break;
                case 'excerpt':
                    field.textContent = post.excerpt || '';
                    break;
                case 'content':
                    field.innerHTML = post.content || '';
                    break;
                case 'date':
                    field.textContent = post.date || '';
                    break;
                case 'author':
                    field.textContent = post.author || '';
                    break;
                case 'authorAvatar':
                    if (post.authorAvatar) {
                        if (field.tagName === 'IMG') {
                            field.src = post.authorAvatar;
                            field.alt = post.author || 'Autor';
                        } else {
                            field.innerHTML = '<img src="' + post.authorAvatar + '" alt="' + (post.author || '') + '" />';
                        }
                    }
                    break;
                case 'featuredImage':
                    if (post.featuredImage) {
                        if (field.tagName === 'IMG') {
                            field.src = post.featuredImage;
                            field.alt = post.title || '';
                        } else {
                            field.style.backgroundImage = 'url(' + post.featuredImage + ')';
                        }
                    }
                    break;
                case 'link':
                    if (field.tagName === 'A') {
                        field.href = post.link || '#';
                    }
                    // El texto del link se mantiene del template
                    break;
                case 'categories':
                    if (post.categories && post.categories.length > 0) {
                        var catNames = post.categories
                            .map(function (c) {
                                return c.name;
                            })
                            .join(', ');
                        field.textContent = catNames;
                    }
                    break;
                case 'tags':
                    if (post.tags && post.tags.length > 0) {
                        var tagNames = post.tags
                            .map(function (t) {
                                return t.name;
                            })
                            .join(', ');
                        field.textContent = tagNames;
                    }
                    break;
                case 'commentCount':
                    field.textContent = (post.commentCount || 0) + ' comentarios';
                    break;
                default:
                    // Para campos meta:xxx, obtener el valor del objeto meta
                    if (fieldType.startsWith('meta:')) {
                        var metaKey = fieldType.substring(5); // Quitar 'meta:'
                        if (post.meta && post.meta[metaKey] !== undefined) {
                            field.textContent = post.meta[metaKey];
                        } else {
                            field.textContent = '[' + fieldType + ']';
                        }
                    }
                    // Para campos acf:xxx
                    else if (fieldType.startsWith('acf:')) {
                        var acfKey = fieldType.substring(4); // Quitar 'acf:'
                        if (post.meta && post.meta[acfKey] !== undefined) {
                            field.textContent = post.meta[acfKey];
                        } else {
                            field.textContent = '[' + fieldType + ']';
                        }
                    }
            }
        });
    }

    // Exportar módulo
    Gbn.ui.renderers.postRenderModules.fields = {
        populateCloneFields: populateCloneFields,
        populateTemplateWithPreview: populateTemplateWithPreview,
        populatePostFields: populatePostFields
    };
})(typeof window !== 'undefined' ? window : this);
