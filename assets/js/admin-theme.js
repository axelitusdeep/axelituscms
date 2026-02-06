const THEME_KEY = 'admin-data-theme';

const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(THEME_KEY, theme);
};

const savedTheme = localStorage.getItem(THEME_KEY);
const systemTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
const currentTheme = savedTheme || systemTheme;

applyTheme(currentTheme);

const toggleTheme = () => {
    const activeTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
};