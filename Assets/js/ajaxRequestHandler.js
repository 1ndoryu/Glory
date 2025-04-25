/**
 * Glory Framework AJAX Helper
 *
 * Provides a standardized method for sending AJAX requests to WordPress backend
 * actions registered via AjaxRequestHandler.
 */
class GloryAjax {

    /**
     * Sends an AJAX request to the WordPress backend using fetch.
     *
     * @param {string} action - The specific action key registered in AjaxRequestHandler.
     * @param {object} [data={}] - An object containing data to send (excluding action and nonce).
     * @param {string|null} [nonce=null] - The nonce value for the specified action. REQUIRED for security.
     * @param {string} [nonceFieldName='_ajax_nonce'] - The field name for the nonce (should match PHP).
     * @returns {Promise<object>} A promise that resolves with the parsed JSON response object
     *                            (typically { success: boolean, data: mixed }) or rejects on network/HTTP error.
     *                            On JSON parsing error, resolves with { success: false, error: 'parse_error', responseText: string }.
     */
    static async send(action, data = {}, nonce = null, nonceFieldName = '_ajax_nonce') {
        // Basic validation
        if (!action) {
            console.error('GloryAjax Error: "action" parameter is required.');
            return Promise.reject({ success: false, message: 'AJAX action not specified.' });
        }
        if (!nonce) {
            console.warn(`GloryAjax Warning: Nonce not provided for action "${action}". Request will likely fail server-side validation.`);
            // Optionally reject immediately: return Promise.reject({ success: false, message: 'Nonce is required.' });
        }
        // Ensure ajaxurl is available (should be localized)
        if (typeof gloryGlobalData === 'undefined' || !gloryGlobalData.ajax_url) {
             console.error('GloryAjax Error: gloryGlobalData.ajax_url is not defined. Ensure ScriptManager localizes it.');
             return Promise.reject({ success: false, message: 'AJAX URL configuration missing.' });
        }
        const ajaxUrl = gloryGlobalData.ajax_url;


        // Prepare data payload using URLSearchParams for 'application/x-www-form-urlencoded'
        const bodyParams = new URLSearchParams();
        bodyParams.append('action', action);

        // Add nonce if provided
        if (nonce) {
            bodyParams.append(nonceFieldName, nonce);
        }

        // Append other data
        for (const key in data) {
            if (Object.hasOwnProperty.call(data, key)) {
                 // Handle potential objects/arrays if needed, though URLSearchParams typically stringifies them simply.
                 // For complex data, consider sending JSON with appropriate headers instead.
                 bodyParams.append(key, data[key]);
            }
        }

        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: bodyParams,
            });

            const responseText = await response.text(); // Get text first for better error diagnosis

            if (!response.ok) {
                 console.error('GloryAjax HTTP Error:', {
                     status: response.status,
                     statusText: response.statusText,
                     responseText: responseText, // Log raw response text
                     action: action,
                     requestData: data
                 });
                 // Try to parse error response if possible, otherwise use status text
                 let errorData = { message: `HTTP error ${response.status} - ${response.statusText}` };
                 try {
                     const parsedError = JSON.parse(responseText);
                     errorData = parsedError.data || errorData; // Use WP standard {success:false, data:{message:..}}
                 } catch (e) {/* Ignore parse error */}

                throw new Error(errorData.message || `HTTP error ${response.status}`);
            }

            // Try parsing JSON
            try {
                return JSON.parse(responseText); // WordPress typically sends {success: true/false, data: ...}
            } catch (jsonError) {
                console.error('GloryAjax JSON Parse Error:', {
                    error: jsonError,
                    responseText: responseText,
                    action: action,
                    requestData: data
                });
                // Return a structured error object instead of just the raw text
                return { success: false, error: 'parse_error', responseText: responseText, message: 'Failed to parse server response.' };
            }

        } catch (error) {
             console.error('GloryAjax Network/Fetch Error:', {
                 error: error,
                 action: action,
                 requestData: data,
                 ajaxUrl: ajaxUrl
             });
             // Return a standardized error object matching WP's {success: false} structure
             return Promise.reject({ success: false, message: error.message || 'A network error occurred.' });
        }
    }
}

window.GloryAjax = GloryAjax; 
