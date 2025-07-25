window.inicializarAlerta = function () {
    let notificationContainer = document.getElementById('glory-notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'glory-notification-container';
        document.body.appendChild(notificationContainer);
    }

    // Sobrescribe alert y confirm solo si no han sido personalizados previamente.
    if (!window.alert.isCustom) {
        window.originalAlert = window.alert;
        window.alert = (message) => showCustomNotification(message, 'alert');
        window.alert.isCustom = true;
    }

    if (!window.confirm.isCustom) {
        window.originalConfirm = window.confirm;
        window.confirm = (message) => showCustomNotification(message, 'confirm');
        window.confirm.isCustom = true;
    }

    function showCustomNotification(message, type) {
        return new Promise((resolve) => {
            const notificationDiv = document.createElement('div');
            notificationDiv.className = `notificacionPersonalizada${type === 'confirm' ? ' alertop' : ''}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'notification-content';
            contentDiv.textContent = message;
            notificationDiv.appendChild(contentDiv);

            const modalBackground = document.getElementById('modalBackground');
            if (modalBackground && type === 'confirm') {
                setTimeout(() => {
                    modalBackground.style.display = 'block';
                    modalBackground.style.visibility = 'visible';
                    modalBackground.style.opacity = '1';
                    modalBackground.style.zIndex = '1002';
                }, 0);

                modalBackground.onclick = () => {
                    closeNotification(false);
                };
            }

            if (type === 'confirm') {
                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'notification-buttons';

                const confirmButton = document.createElement('button');
                confirmButton.textContent = 'Confirmar';
                confirmButton.className = 'botonprincipal';
                confirmButton.onclick = () => {
                    closeNotification(true);
                };

                const cancelButton = document.createElement('button');
                cancelButton.textContent = 'Cancelar';
                cancelButton.className = 'botonsecundario';
                cancelButton.onclick = () => {
                    closeNotification(false);
                };

                buttonsDiv.appendChild(cancelButton);
                buttonsDiv.appendChild(confirmButton);
                notificationDiv.appendChild(buttonsDiv);
            } else {
                setTimeout(() => {
                    closeNotification();
                }, 3000);
            }

            // Añade la notificación al contenedor principal
            notificationContainer.appendChild(notificationDiv);

            function closeNotification(result) {
                // Agrega una clase para la animación de salida
                notificationDiv.classList.add('notification-fade-out');

                // Elimina el elemento del DOM después de que termine la animación
                notificationDiv.addEventListener('animationend', () => {
                    if (notificationContainer.contains(notificationDiv)) {
                        notificationContainer.removeChild(notificationDiv);
                    }
                }, { once: true });

                if (modalBackground && type === 'confirm') {
                    modalBackground.style.display = 'none';
                    modalBackground.style.visibility = 'hidden';
                    modalBackground.style.opacity = '0';
                    modalBackground.style.zIndex = 'auto';
                    modalBackground.onclick = null;
                }
                resolve(result);
            }
        });
    }
};

document.addEventListener('gloryRecarga', inicializarAlerta);