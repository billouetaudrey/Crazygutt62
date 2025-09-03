// Gestion du thème (clair/sombre) avec localStorage
(function(){
    const key = 'theme';
    const html = document.documentElement;
    
    // Fonction unique pour appliquer le thème sur l'élément <html>
    function applyTheme(t){
        if(t === 'light'){
            html.classList.add('light');
        } else {
            html.classList.remove('light');
        }
    }
    
    // Applique le thème sauvegardé ou 'dark' par défaut
    const saved = localStorage.getItem(key) || 'dark';
    applyTheme(saved);
    
    // Fonction accessible globalement pour basculer le thème
    window.toggleTheme = function(){
        const now = html.classList.contains('light') ? 'dark' : 'light';
        localStorage.setItem(key, now);
        applyTheme(now);
    };
})();
