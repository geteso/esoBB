/**
 * This file is part of the esoBB project, a derivative of esoTalk.
 * It has been modified by several contributors.  (contact@geteso.org)
 * Copyright (C) 2025 esoTalk, esoBB.  <https://geteso.org>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// Installer JavaScript.
var Install = {

fieldsValidated: {}, // An array of fields and if they've been validated (set by PHP).
formConfig: null, // Form configuration (set in init)
hasManualModification: false, // Track if user has manually modified any field

// Initialize: set up form validation using Form.
init: function() {
	// Create formConfig structure for Form
	Install.formConfig = {
		formId: "info",
		fields: {},
		fieldsValidated: Install.fieldsValidated,
		timeouts: {},
		submitButtonId: "installSubmit",
		ajaxController: "install",
		confirmPasswordField: "adminPass"
	};

	// Populate fields object from fieldsValidated
	for (var fieldId in Install.fieldsValidated) {
		Install.formConfig.fields[fieldId] = {};
	}

	// Initialize Form
	Form.init(Install.formConfig);

	// Override Form.updateButtonState to directly manage installSubmit button by ID
	var originalUpdateButtonState = Form.updateButtonState;
	Form.updateButtonState = function(formConfig) {
		// Call beforeButtonUpdate hook if provided
		if (formConfig.hooks && formConfig.hooks.beforeButtonUpdate) {
			formConfig.hooks.beforeButtonUpdate(formConfig);
		}
		
		// Calculate form completion status - use Install.fieldsValidated directly to ensure we're checking the right object
		var formCompleted = true;
		if (formConfig.customButtonState) {
			formCompleted = formConfig.customButtonState(formConfig);
		} else {
			// Check Install.fieldsValidated directly (same object as formConfig.fieldsValidated, but be explicit)
			for (var fieldId in Install.fieldsValidated) {
				if (!Install.fieldsValidated[fieldId]) {
					formCompleted = false;
					break;
				}
			}
		}
		
		// Directly manage installSubmit button by ID
		var button = getById("installSubmit");
		if (button) {
			// Remove all disabled classes (buttonDisabled, bigDisabled, disabled)
			button.className = button.className.replace(/\s*(buttonD|bigD|d)isabled\s*/g, " ").trim().replace(/\s+/g, " ");
			if (formCompleted) {
				// Enable: just "button"
				button.removeAttribute("disabled");
				button.disabled = false;
			} else {
				// Disable: "button disabled"
				button.className += " disabled";
				button.disabled = true;
			}
		}
		
		// Call afterButtonUpdate hook if provided
		if (formConfig.hooks && formConfig.hooks.afterButtonUpdate) {
			formConfig.hooks.afterButtonUpdate(formConfig);
		}
	};
	
	// Set initial button state
	Form.updateButtonState(Install.formConfig);

	// Override Form.validateField to add null check for this.result
	var originalValidateField = Form.validateField;
	Form.validateField = function(field, formConfig) {
		clearTimeout(formConfig.timeouts[field.id]);
		var fieldElement = field;
		if (formConfig.hooks && formConfig.hooks.beforeValidate) {
			formConfig.hooks.beforeValidate(fieldElement, formConfig);
		}
		formConfig.timeouts[field.id] = setTimeout(function() {
			var value = fieldElement.value;
			var isEmpty = !value || value.length === 0;
			var postData = "action=validate&field=" + fieldElement.id + "&form=" + formConfig.formId + "&value=" + encodeURIComponent(value);
			if (fieldElement.id == "confirm" && formConfig.confirmPasswordField) {
				var passwordField = getById(formConfig.confirmPasswordField);
				if (passwordField) postData += "&password=" + encodeURIComponent(passwordField.value);
			}
			if (fieldElement.id == "confirm" && formConfig.newPasswordField) {
				var newPasswordField = getById(formConfig.newPasswordField);
				if (newPasswordField) postData += "&newPassword=" + encodeURIComponent(newPasswordField.value);
			}
			Ajax.request({
				"url": eso.baseURL + "ajax.php?controller=" + formConfig.ajaxController,
				"success": function() {
					// Add null check for this.result
					if (!this.result) {
						return;
					}
					var wasValidated = this.result.validated;
					var message = this.result.message;
					formConfig.fieldsValidated[fieldElement.id] = wasValidated;
					
					// On first manual modification, validate all other fields that have values
					if (!Install.hasManualModification && wasValidated) {
						Install.hasManualModification = true;
						// Loop through all fields and validate those with values that haven't been validated yet
						for (var fieldId in Install.fieldsValidated) {
							if (fieldId !== fieldElement.id && !Install.fieldsValidated[fieldId]) {
								var otherField = getById(fieldId);
								if (otherField && otherField.value) {
									Form.validateField(otherField, formConfig);
								}
							}
						}
					}
					
					if (formConfig.hooks && formConfig.hooks.afterValidate) {
						var hookResult = formConfig.hooks.afterValidate(fieldElement, wasValidated, message, formConfig);
						if (hookResult) {
							wasValidated = hookResult.wasValidated !== undefined ? hookResult.wasValidated : wasValidated;
							message = hookResult.message !== undefined ? hookResult.message : message;
						}
					}
					var messageEl = getById(fieldElement.id + "-message");
					if (messageEl) messageEl.innerHTML = message;
					if (formConfig.confirmPasswordField) {
						Form.handleConfirmPassword(fieldElement.id, formConfig.confirmPasswordField, formConfig, isEmpty);
					} else if (formConfig.newPasswordField) {
						Form.handleConfirmPassword(fieldElement.id, formConfig.newPasswordField, formConfig, isEmpty);
					}
					Form.updateButtonState(formConfig);
				},
				"post": postData
			});
		}, 500);
	};

	// Override Ajax.request to add password parameter for adminConfirm validation
	var originalAjaxRequest = Ajax.request;
	Ajax.request = function(request) {
		if (request && request.post && request.post.indexOf("field=adminConfirm") !== -1 && Install.formConfig && Install.formConfig.confirmPasswordField) {
			var passwordField = getById(Install.formConfig.confirmPasswordField);
			if (passwordField && request.post.indexOf("password=") === -1) {
				request.post += "&password=" + encodeURIComponent(passwordField.value);
			}
		}
		return originalAjaxRequest.call(this, request);
	};
},

// Change language: update language via AJAX.
changeLanguage: function(language) {
	// Get CSRF token from form
	var form = document.getElementById("container") ? (document.getElementById("container").closest ? document.getElementById("container").closest("form") : document.getElementById("container").parentNode) : null;
	var token = "";
	if (form) {
		var tokenInput = form.querySelector('input[name="token"]');
		if (tokenInput) token = tokenInput.value;
	}
	
	// Make AJAX request to change language
	Ajax.request({
		"url": "install/ajax.php",
		"post": "action=changeLanguage&language=" + encodeURIComponent(language) + "&token=" + encodeURIComponent(token),
		"success": function() {
			if (this.success) {
				// Update CSRF token if returned
				if (this.token && form) {
					var tokenInput = form.querySelector('input[name="token"]');
					if (tokenInput) tokenInput.value = this.token;
				}
				// Reload page to update language strings
				window.location.reload();
			} else if (this.message) {
				alert(this.message);
			}
		}
	});
}

};
