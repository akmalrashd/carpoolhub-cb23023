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

    function handleLicenseUpload(input) {
        const label    = document.getElementById('license-upload-label');
        const filename = document.getElementById('license-filename');
        const icon     = document.getElementById('license-icon');
        if (input.files && input.files[0]) {
            label.classList.add('has-file');
            filename.textContent = input.files[0].name;
            icon.className = 'fa-solid fa-circle-check';
        } else {
            label.classList.remove('has-file');
            filename.textContent = 'Upload front of your driving license';
            icon.className = 'fa-solid fa-id-card';
        }
    }

    function handleSelfieUpload(input) {
        const label    = document.getElementById('selfie-upload-label');
        const filename = document.getElementById('selfie-filename');
        const icon     = document.getElementById('selfie-icon');
        if (input.files && input.files[0]) {
            label.classList.add('has-file');
            filename.textContent = input.files[0].name;
            icon.className = 'fa-solid fa-circle-check';
        } else {
            label.classList.remove('has-file');
            filename.textContent = 'Upload selfie holding your license';
            icon.className = 'fa-solid fa-user-shield';
        }
    }

    // Show/hide vehicle section based on role selection
    const roleInputs = document.querySelectorAll('input[name="role"]');
    const vehicleSection = document.getElementById('vehicle-section');

    function updateVehicleSection() {
        const selected = document.querySelector('input[name="role"]:checked');
        if (selected && selected.value === 'driver') {
            vehicleSection.classList.add('visible');
        } else {
            vehicleSection.classList.remove('visible');
        }
    }

    roleInputs.forEach(input => {
        input.addEventListener('change', updateVehicleSection);
    });
