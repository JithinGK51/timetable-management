            </div><!-- /content-wrapper -->
        </main><!-- /main-content -->
    </div><!-- /admin-wrapper -->
    
    <!-- User Dropdown Menu -->
    <div id="userDropdown" style="display: none; position: fixed; top: 60px; right: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); min-width: 180px; z-index: 1001;">
        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-color);">
            <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($currentAdmin['name']); ?></div>
            <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($currentAdmin['email'] ?? ''); ?></div>
        </div>
        <a href="/ttc/modules/settings/index.php" style="display: block; padding: 10px 16px; color: var(--text-dark); font-size: 13px; transition: background 0.2s;">
            <i class="fas fa-cog" style="width: 20px;"></i> Settings
        </a>
        <a href="/ttc/modules/auth/logout.php" style="display: block; padding: 10px 16px; color: var(--danger-color); font-size: 13px; transition: background 0.2s; border-top: 1px solid var(--border-color);">
            <i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout
        </a>
    </div>
    
    <script src="/ttc/assets/js/main.js"></script>
</body>
</html>
