(function () {
	function initHoverSubmenus() {
		const nav = document.querySelector('.siteMenuNav');
		if (!nav) return;
		if (!window.matchMedia('(min-width: 1000px)').matches) return;

		const items = nav.querySelectorAll('ul.menu > li');
		let currentItem = null;
		let closeTimer = null;
		let repositionObserver = null;
		let globalMoveBound = false;

		function getSubmenu(li) {
			return li.querySelector(':scope > ul.sub-menu') || li.__portalSubmenu || null;
		}

		function moveSubmenuToNavRoot(submenu, li) {
			if (!submenu || !nav) return;
			if (submenu.__portalParent === nav) return;
			submenu.__portalOriginalParent = submenu.parentElement;
			submenu.__portalNextSibling = submenu.nextSibling;
			submenu.__portalParent = nav;
			submenu.__portalOwner = li;
			li.__portalSubmenu = submenu;
			// Mantener estilos y selectores: sigue siendo descendiente de .siteMenuNav
			nav.appendChild(submenu);
			submenu.classList.add('submenu-portal');
			submenu.style.position = 'absolute';
			submenu.style.zIndex = '1004';
			// Asegurar dimensiones correctas fuera del selector específico original
			submenu.style.minWidth = submenu.style.minWidth || '220px';
			submenu.style.height = 'auto';
		}

		function restoreSubmenu(submenu) {
			if (!submenu || !submenu.__portalOriginalParent) return;
			// Restaurar al lugar original en el DOM
			if (submenu.__portalNextSibling && submenu.__portalNextSibling.parentNode === submenu.__portalOriginalParent) {
				submenu.__portalOriginalParent.insertBefore(submenu, submenu.__portalNextSibling);
			} else {
				submenu.__portalOriginalParent.appendChild(submenu);
			}
			// Limpiar estilos inline
			submenu.style.left = '';
			submenu.style.top = '';
			submenu.style.display = '';
			submenu.style.position = '';
			submenu.style.zIndex = '';
			submenu.style.minWidth = '';
			submenu.style.height = '';
			// Limpiar referencias
			submenu.classList.remove('submenu-portal');
			if (submenu.__portalOwner && submenu.__portalOwner.__portalSubmenu === submenu) {
				try { delete submenu.__portalOwner.__portalSubmenu; } catch(_) { submenu.__portalOwner.__portalSubmenu = null; }
			}
			submenu.__portalOriginalParent = null;
			submenu.__portalNextSibling = null;
			submenu.__portalParent = null;
			submenu.__portalOwner = null;
		}

		function measureSubmenu(submenu) {
			const prevDisplay = submenu.style.display;
			const prevVisibility = submenu.style.visibility;
			const prevPointer = submenu.style.pointerEvents;
			submenu.style.display = 'block';
			submenu.style.visibility = 'hidden';
			submenu.style.pointerEvents = 'none';
			const width = submenu.offsetWidth;
			const height = submenu.offsetHeight;
			submenu.style.display = prevDisplay;
			submenu.style.visibility = prevVisibility;
			submenu.style.pointerEvents = prevPointer;
			return { width, height };
		}

		function positionSubmenu(submenu, li) {
			const navRect = nav.getBoundingClientRect();
			const liRect = li.getBoundingClientRect();
			const { width: sw, height: sh } = measureSubmenu(submenu);
			const gap = 20; // calc(100% + 20px)
			let left = (liRect.left + liRect.width / 2) - navRect.left - (sw / 2);
			let top = (liRect.bottom - navRect.top) + gap;
			// Clamp dentro del nav
			left = Math.max(0, Math.min(left, navRect.width - sw));
			// Aplicar
			submenu.style.left = left + 'px';
			submenu.style.top = top + 'px';
		}

		function openSubmenu(li) {
			if (currentItem && currentItem !== li) {
				closeSubmenu(currentItem, true);
			}
			const submenu = getSubmenu(li);
			if (!submenu) return;
			currentItem = li;
			clearTimeout(closeTimer);
			li.classList.add('is-open');
			moveSubmenuToNavRoot(submenu, li);
			positionSubmenu(submenu, li);
			submenu.style.display = 'block';
			// Reposicionar en scroll (ligero) para mantener anclaje
			if (!repositionObserver) {
				repositionObserver = () => {
					if (!currentItem) return;
					const sm = getSubmenu(currentItem);
					if (sm) positionSubmenu(sm, currentItem);
				};
				window.addEventListener('scroll', repositionObserver, { passive: true });
				window.addEventListener('resize', repositionObserver, { passive: true });
			}
		}

		function closeSubmenu(li, immediate = false) {
			const submenu = getSubmenu(li);
			if (!submenu) return;
			function doClose() {
				li.classList.remove('is-open');
				submenu.style.display = 'none';
				restoreSubmenu(submenu);
				if (currentItem === li) currentItem = null;
			}
			if (immediate) {
				doClose();
			} else {
				closeTimer = setTimeout(doClose, 260);
			}
		}

		items.forEach(li => {
			const submenu = getSubmenu(li);
			if (!submenu) return;

			li.addEventListener('mouseenter', () => openSubmenu(li));
			li.addEventListener('mouseleave', () => closeSubmenu(li));

			submenu.addEventListener('mouseenter', () => {
				clearTimeout(closeTimer);
			});
			submenu.addEventListener('mouseleave', () => closeSubmenu(li));

			// Accesibilidad por teclado
			li.addEventListener('focusin', () => openSubmenu(li));
			li.addEventListener('focusout', (e) => {
				if (!li.contains(e.relatedTarget)) {
					closeSubmenu(li, true);
				}
			});
		});

		// Cierre general si el mouse sale del nav
		nav.addEventListener('mouseleave', () => {
			if (!currentItem) return;
			closeSubmenu(currentItem);
		});

		// Cierre fiable: si el cursor no está sobre el li activo ni su submenú, cerrar
		if (!globalMoveBound) {
			globalMoveBound = true;
			document.addEventListener('mousemove', (e) => {
				if (!currentItem) return;
				const li = currentItem;
				const submenu = getSubmenu(li);
				const target = e.target;
				const overActive = li.contains(target) || (submenu && submenu.contains(target));
				if (!overActive) {
					clearTimeout(closeTimer);
					closeTimer = setTimeout(() => {
						if (currentItem === li) closeSubmenu(li, true);
					}, 240);
				} else {
					clearTimeout(closeTimer);
				}
			}, { passive: true });
		}
	}

	function setup() {
		initHoverSubmenus();
	}

	document.addEventListener('DOMContentLoaded', setup);
	window.addEventListener('load', setup);
	document.addEventListener('gloryRecarga', setup);

	// Al redimensionar, cerramos cualquier submenú abierto para evitar estados inconsistentes
	window.addEventListener('resize', () => {
		const openItems = document.querySelectorAll('.siteMenuNav ul.menu > li.is-open');
		openItems.forEach(li => {
			const ul = li.querySelector(':scope > ul.sub-menu');
			if (!ul) return;
			ul.style.display = '';
			li.classList.remove('is-open');
			if (ul.__portalOriginalParent) {
				if (ul.__portalNextSibling && ul.__portalNextSibling.parentNode === ul.__portalOriginalParent) {
					ul.__portalOriginalParent.insertBefore(ul, ul.__portalNextSibling);
				} else {
					ul.__portalOriginalParent.appendChild(ul);
				}
				ul.style.left = '';
				ul.style.top = '';
				ul.style.position = '';
				ul.style.zIndex = '';
				ul.__portalOriginalParent = null;
				ul.__portalNextSibling = null;
				ul.__portalParent = null;
			}
		});
	});
})();

