import { ref, watch } from 'vue'

// Singleton instance - shared across all uses
let instance = null

function createDarkModeInstance() {
    const isDark = ref(false)
    const isLoaded = ref(false)

    const applyTheme = () => {
        try {
            const html = document.documentElement
            if (isDark.value) {
                html.classList.add('dark')
                html.style.colorScheme = 'dark'
                if (document.body) document.body.style.colorScheme = 'dark'
            } else {
                html.classList.remove('dark')
                html.style.colorScheme = 'light'
                if (document.body) document.body.style.colorScheme = 'light'
            }
            console.log('✓ Dark mode applied:', isDark.value)
        } catch (e) {
            console.error('✗ Error applying theme:', e)
        }
    }

    const toggleDarkMode = () => {
        console.log('🌙 toggleDarkMode called, current isDark:', isDark.value)
        isDark.value = !isDark.value
        console.log('🌙 Toggled isDark to:', isDark.value)
        try {
            localStorage.setItem('darkMode', isDark.value ? 'dark' : 'light')
            console.log('✓ Dark mode saved to localStorage:', isDark.value)
        } catch (e) {
            console.error('✗ Error saving to localStorage:', e)
        }
        applyTheme()
    }

    const initTheme = () => {
        if (isLoaded.value) return // Already initialized

        try {
            // Check localStorage first
            const saved = localStorage.getItem('darkMode')
            if (saved !== null) {
                isDark.value = saved === 'dark'
                console.log('✓ Dark mode loaded from localStorage:', isDark.value)
            } else {
                // Check system preference
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
                isDark.value = prefersDark
                console.log('✓ Dark mode from system preference:', isDark.value)
            }

            // Save to localStorage to keep it in sync
            localStorage.setItem('darkMode', isDark.value ? 'dark' : 'light')
            console.log('✓ Dark mode saved to localStorage:', isDark.value)
        } catch (e) {
            console.error('✗ Error initializing theme:', e)
            isDark.value = false
        }

        applyTheme()
        isLoaded.value = true
    }

    // Watch for changes
    watch(isDark, () => {
        applyTheme()
    })

    return {
        isDark,
        isLoaded,
        toggleDarkMode,
        initTheme,
    }
}

export function useDarkMode() {
    if (!instance) {
        console.log('🌙 Creating new dark mode instance')
        instance = createDarkModeInstance()
    } else {
        console.log('🌙 Using existing dark mode instance')
    }
    return instance
}
