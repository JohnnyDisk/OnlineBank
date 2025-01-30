class PasswordValidator {
    constructor(passwordInput, confirmInput, strengthMeter, requirements) {
        this.passwordInput = passwordInput;
        this.confirmInput = confirmInput;
        this.strengthMeter = strengthMeter;
        this.requirements = requirements;
        
        this.rules = {
            length: { regex: /.{8,}/, text: "At least 8 characters" },
            uppercase: { regex: /[A-Z]/, text: "At least one uppercase letter" },
            lowercase: { regex: /[a-z]/, text: "At least one lowercase letter" },
            number: { regex: /[0-9]/, text: "At least one number" },
            special: { regex: /[^A-Za-z0-9]/, text: "At least one special character" }
        };
        
        this.init();
    }
    
    init() {
        // Create requirement list items
        Object.entries(this.rules).forEach(([key, rule]) => {
            const li = document.createElement('li');
            li.id = `req-${key}`;
            li.innerHTML = `<i class="bi bi-x-circle text-danger"></i> ${rule.text}`;
            this.requirements.appendChild(li);
        });
        
        // Add event listeners
        this.passwordInput.addEventListener('input', () => this.checkPassword());
        if (this.confirmInput) {
            this.confirmInput.addEventListener('input', () => this.checkMatch());
        }
    }
    
    checkPassword() {
        const password = this.passwordInput.value;
        let strength = 0;
        
        // Check each requirement
        Object.entries(this.rules).forEach(([key, rule]) => {
            const li = document.getElementById(`req-${key}`);
            const meets = rule.regex.test(password);
            
            if (meets) {
                li.innerHTML = `<i class="bi bi-check-circle text-success"></i> ${rule.text}`;
                strength += 20;
            } else {
                li.innerHTML = `<i class="bi bi-x-circle text-danger"></i> ${rule.text}`;
            }
        });
        
        // Update strength meter
        this.strengthMeter.style.width = `${strength}%`;
        if (strength <= 40) {
            this.strengthMeter.className = 'progress-bar bg-danger';
        } else if (strength <= 80) {
            this.strengthMeter.className = 'progress-bar bg-warning';
        } else {
            this.strengthMeter.className = 'progress-bar bg-success';
        }
        
        this.checkMatch();
        return strength === 100;
    }
    
    checkMatch() {
        if (!this.confirmInput) return true;
        
        const password = this.passwordInput.value;
        const confirm = this.confirmInput.value;
        const matches = password === confirm;
        
        this.confirmInput.classList.toggle('is-valid', matches && confirm.length > 0);
        this.confirmInput.classList.toggle('is-invalid', !matches && confirm.length > 0);
        
        return matches;
    }
    
    isValid() {
        return this.checkPassword() && this.checkMatch();
    }
} 