<div class="auth-card">
    <div class="auth-header">
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Sign in to your RCE account</p>
    </div>
    
    <form id="loginForm" class="auth-form">
        <div class="form-group">
            <label for="username">Email or Username</label>
            <input type="text" id="username" name="username" required autocomplete="username" placeholder="Enter your email or username">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
        </div>
        
        <div class="form-group" style="display: flex; align-items: center; margin-bottom: 2rem;">
            <input type="checkbox" id="remember" name="remember" style="width: auto; margin-right: 0.5rem;">
            <label for="remember" style="margin-bottom: 0; font-weight: normal;">Remember me</label>
        </div>
        
        <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        
        <div id="loginError" class="error-message" style="display: none;"></div>
    </form>
    
    <div class="auth-footer">
        <p><a href="/forgot-password">Forgot your password?</a></p>
        <p>Don't have an account? <a href="/register">Sign up here</a></p>
        <p style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
            <a href="/" style="color: #666;">← Back to Home</a>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorContainer = document.getElementById('loginError');
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const formData = new FormData(this);
        
        try {
            loading.show(submitBtn);
            errorContainer.style.display = 'none';
            
            const credentials = Object.fromEntries(formData.entries());
            const response = await api.login(credentials);
            
            if (response.success) {
                // Redirect based on role
                const redirectUrl = response.redirect_url || getDefaultRedirect(response.role);
                window.location.href = redirectUrl;
            } else {
                throw new Error(response.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            errorContainer.textContent = error.message || 'Login failed. Please check your credentials and try again.';
            errorContainer.style.display = 'block';
        } finally {
            loading.hide(submitBtn);
        }
    });
    
    function getDefaultRedirect(role) {
        const redirects = {
            'super_admin': '/saportal/',
            'tenant_admin': '/admin/',
            'talent': '/talent/',
            'client': '/client/feed'
        };
        return redirects[role] || '/client/feed';
    }
});
</script>
