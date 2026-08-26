/* Extracted from resources/views/auth/register.blade.php — cacheable. */
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // The accept="" attribute is only a hint to the OS file picker — it does
    // not stop someone choosing "All Files" and picking a PDF or a .heic a
    // browser doesn't recognise as image/*, so the real accept/reject check
    // for "images only" happens here against the file's own MIME type.
    const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function handleFileUpload(input, prefix, emptyText, emptyIconClass, maxSizeMb) {
        const label      = document.getElementById(`${prefix}-upload-label`);
        const filename   = document.getElementById(`${prefix}-filename`);
        const icon       = document.getElementById(`${prefix}-icon`);
        const errorEl    = document.getElementById(`${prefix}-client-error`);
        const errorText  = errorEl ? errorEl.querySelector('span') : null;

        const showError = (message) => {
            input.value = '';
            label.classList.remove('has-file');
            filename.textContent = emptyText;
            icon.className = emptyIconClass;
            if (errorEl && errorText) {
                errorText.textContent = message;
                errorEl.hidden = false;
            }
        };

        if (errorEl) errorEl.hidden = true;

        const file = input.files && input.files[0];
        if (!file) {
            label.classList.remove('has-file');
            filename.textContent = emptyText;
            icon.className = emptyIconClass;
            return;
        }

        if (!ACCEPTED_IMAGE_TYPES.includes(file.type)) {
            showError('Only JPG, PNG, or WEBP images are allowed.');
            return;
        }

        if (file.size > maxSizeMb * 1024 * 1024) {
            showError(`Image must not exceed ${maxSizeMb}MB.`);
            return;
        }

        label.classList.add('has-file');
        filename.textContent = file.name;
        icon.className = 'fa-solid fa-circle-check';
    }

    function handleLicenseUpload(input) {
        handleFileUpload(input, 'license', 'Upload front of your driving license', 'fa-solid fa-id-card', 4);
    }

    function handleSelfieUpload(input) {
        handleFileUpload(input, 'selfie', 'Upload selfie holding your license', 'fa-solid fa-user-shield', 5);
    }

    // ── Step wizard ──────────────────────────────────────────────────────
    // Step 3 (vehicle & verification) only applies to drivers, so the active
    // step list is recomputed from the current role selection rather than
    // being a fixed [1,2,3,4] — a passenger's flow is just [1,2,4].
    (() => {
        const form = document.getElementById('register-form');
        if (!form) return;

        const steps = Array.from(form.querySelectorAll('.wizard-step'));
        const progressFill = document.getElementById('wizard-progress-fill');
        const progressLabel = document.getElementById('wizard-progress-label');
        const backBtn = document.getElementById('wizard-back-btn');
        const nextBtn = document.getElementById('wizard-next-btn');
        const submitBtn = document.getElementById('wizard-submit-btn');
        const vehicleStep = document.getElementById('vehicle-section');
        const licenseInput = document.getElementById('driving_license_photo');
        const selfieInput = document.getElementById('selfie_photo');

        // Vehicle/verification fields are only mandatory when step 3 is
        // actually part of the flow (driver role) — kept in sync by
        // activeSteps() below rather than left statically required, since a
        // hidden required file input for a passenger would never block
        // submission anyway (form has novalidate) but we still want
        // per-step reportValidity() checks to behave correctly either way.
        const vehicleRequiredInputs = vehicleStep
            ? Array.from(vehicleStep.querySelectorAll('#vehicle_model, #vehicle_plate, #driving_license_expiry'))
            : [];

        const currentRole = () => {
            const checked = form.querySelector('input[name="role"]:checked');
            return checked ? checked.value : 'passenger';
        };

        const activeSteps = () => {
            const isDriver = currentRole() === 'driver';
            return steps.filter((step) => {
                const num = step.dataset.step;
                return num !== '3' || isDriver;
            });
        };

        let currentStep = null;

        const setRequiredForActiveRole = () => {
            const isDriver = currentRole() === 'driver';
            vehicleRequiredInputs.forEach((input) => { input.required = isDriver; });
            if (licenseInput) licenseInput.required = isDriver;
            if (selfieInput) selfieInput.required = isDriver;
        };

        const focusFirstField = (step) => {
            const field = step.querySelector('input:not([type="hidden"]), select, textarea');
            if (field) {
                try { field.focus({ preventScroll: true }); } catch (_e) { field.focus(); }
            }
        };

        const showStep = (stepEl) => {
            steps.forEach((step) => step.classList.toggle('is-active', step === stepEl));
            currentStep = stepEl;

            const list = activeSteps();
            const index = list.indexOf(stepEl);
            const total = list.length;

            progressFill.style.width = `${((index + 1) / total) * 100}%`;
            progressLabel.textContent = `Step ${index + 1} of ${total}`;

            backBtn.hidden = index === 0;
            const isLast = index === total - 1;
            nextBtn.hidden = isLast;
            submitBtn.hidden = !isLast;

            focusFirstField(stepEl);
        };

        const stepByNumber = (num) => steps.find((step) => step.dataset.step === String(num));

        const goToStep = (direction) => {
            const list = activeSteps();
            const index = list.indexOf(currentStep);
            const nextIndex = index + direction;
            if (nextIndex < 0 || nextIndex >= list.length) return;
            showStep(list[nextIndex]);
        };

        // Only the fields inside the currently-visible step are checked —
        // reportValidity() on an individual input shows the browser's own
        // inline validation bubble without needing custom error UI.
        const validateCurrentStep = () => {
            const inputs = currentStep.querySelectorAll('input[required], select[required]');
            for (const input of inputs) {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    return false;
                }
            }
            return true;
        };

        nextBtn.addEventListener('click', () => {
            if (!validateCurrentStep()) return;
            goToStep(1);
        });

        backBtn.addEventListener('click', () => goToStep(-1));

        form.querySelectorAll('input[name="role"]').forEach((input) => {
            input.addEventListener('change', setRequiredForActiveRole);
        });

        const confirmInput = document.getElementById('password_confirmation');
        const confirmError = document.getElementById('confirm-password-error');
        form.addEventListener('submit', (event) => {
            const password = document.getElementById('password').value;
            const confirmation = confirmInput.value;
            if (password !== confirmation) {
                event.preventDefault();
                confirmError.hidden = false;
                confirmInput.setCustomValidity('Passwords do not match.');
                showStep(stepByNumber(4));
                confirmInput.reportValidity();
                return;
            }
            confirmError.hidden = true;
            confirmInput.setCustomValidity('');
        });
        confirmInput.addEventListener('input', () => {
            confirmError.hidden = true;
            confirmInput.setCustomValidity('');
        });

        setRequiredForActiveRole();

        // Land directly on the step the server flagged (after a failed
        // round-trip) instead of always restarting at step 1.
        const initialStepNum = parseInt(form.dataset.initialStep || '1', 10);
        const initialStep = stepByNumber(initialStepNum) || steps[0];
        showStep(initialStep);
    })();
