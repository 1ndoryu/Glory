;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

    // Backward compatibility
    Gbn.ui.panelTheme = {
        renderPageSettingsForm: Gbn.ui.theme.render.renderPageSettingsForm,
        renderThemeSettingsForm: Gbn.ui.theme.render.renderThemeSettingsForm,
        applyThemeSettings: Gbn.ui.theme.applicator.applyThemeSettings,
        applyPageSettings: Gbn.ui.theme.applicator.applyPageSettings
    };

})(window);
