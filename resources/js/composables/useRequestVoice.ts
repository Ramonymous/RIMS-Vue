import { ref } from 'vue';

interface RequestItem {
    id: string;
    part: {
        part_number: string;
    };
    is_urgent: boolean;
}

interface AnnouncementState {
    announcedIds: Set<string>;
    lastReminderTime: Map<string, number>;
    isInitialized: boolean;
    permissionGranted: boolean;
}

const STORAGE_KEY = 'rims_announced_items';
const MAX_ANNOUNCED_HISTORY = 1000; // Prevent localStorage bloat
const MAX_SPELLED_CACHE = 100; // Limit spelling cache size

// Shared state across all instances
const state = ref<AnnouncementState>({
    announcedIds: new Set<string>(),
    lastReminderTime: new Map<string, number>(),
    isInitialized: false,
    permissionGranted: false,
});

// Cache for spelled part numbers (performance optimization)
const spelledCache = new Map<string, string>();

/**
 * Convert part number to spelled out characters
 * Example: "ABC-123" becomes "A B C 1 2 3" (dash is silent)
 * Uses caching for performance optimization
 */
function spellPartNumber(partNumber: string): string {
    // Check cache first
    if (spelledCache.has(partNumber)) {
        return spelledCache.get(partNumber)!;
    }

    const spelled = partNumber
        .split('')
        .map((char) => {
            if (char === '-') return ''; // Silent dash
            if (char === '_') return ''; // Silent underscore
            if (char === '/') return ''; // Silent slash
            if (char === '.') return ''; // Silent dot
            if (char === ' ') return ''; // Silent space
            return char;
        })
        .filter((char) => char !== '') // Remove empty strings
        .join(' ');

    // Cache the result
    spelledCache.set(partNumber, spelled);

    // Prevent cache from growing too large (FIFO)
    if (spelledCache.size > MAX_SPELLED_CACHE) {
        const firstKey = spelledCache.keys().next().value;
        if (firstKey) spelledCache.delete(firstKey);
    }

    return spelled;
}

/**
 * Speak text using Web Speech API
 */
function speak(
    text: string,
    options: { urgent?: boolean; onEnd?: () => void } = {},
): Promise<void> {
    return new Promise((resolve, reject) => {
        if (!state.value.permissionGranted) {
            resolve();
            return;
        }

        // Cancel current speech if urgent message
        if (options.urgent && window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
        }

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID'; // Indonesian
        utterance.rate = options.urgent ? 1.1 : 0.95;
        utterance.pitch = options.urgent ? 1.2 : 1.0;
        utterance.volume = 1.0;

        utterance.onend = () => {
            currentSpeech = null;
            options.onEnd?.();
            resolve();
        };

        utterance.onerror = () => {
            currentSpeech = null;
            reject();
        };

        currentSpeech = utterance;
        window.speechSynthesis.speak(utterance);
    });
}

/**
 * Load announced IDs from localStorage
 */
function loadAnnouncedIds(): void {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const ids = JSON.parse(stored) as string[];
            state.value.announcedIds = new Set(ids);
        }
    } catch (error) {
        console.error('Failed to load announced IDs:', error);
    }
}

/**
 * Save announced IDs to localStorage with quota management
 */
function saveAnnouncedIds(): void {
    try {
        const idsArray = Array.from(state.value.announcedIds);

        // Keep only recent IDs to prevent localStorage bloat
        const recentIds = idsArray.slice(-MAX_ANNOUNCED_HISTORY);

        // Update state with trimmed IDs
        if (recentIds.length < idsArray.length) {
            state.value.announcedIds.clear();
            recentIds.forEach((id) => state.value.announcedIds.add(id));
        }

        localStorage.setItem(STORAGE_KEY, JSON.stringify(recentIds));
    } catch (error) {
        console.error('Failed to save announced IDs:', error);
    }
}

/**
 * Request user interaction for speech permission
 */
function requestPermission(): Promise<boolean> {
    return new Promise((resolve) => {
        if (state.value.permissionGranted) {
            resolve(true);
            return;
        }

        // Test speech synthesis with empty utterance
        const test = new SpeechSynthesisUtterance('');
        test.onend = () => {
            state.value.permissionGranted = true;
            resolve(true);
        };
        test.onerror = () => {
            state.value.permissionGranted = false;
            resolve(false);
        };

        window.speechSynthesis.speak(test);
    });
}

/**
 * Initialize the voice announcement system
 */
async function initialize(): Promise<boolean> {
    if (state.value.isInitialized) {
        return state.value.permissionGranted;
    }

    // Check if speech synthesis is supported
    if (!('speechSynthesis' in window)) {
        console.warn('Speech Synthesis not supported in this browser');
        state.value.isInitialized = true;
        return false;
    }

    loadAnnouncedIds();
    state.value.isInitialized = true;

    return state.value.permissionGranted;
}

/**
 * Announce a new request item
 */
async function announceItem(item: RequestItem): Promise<void> {
    if (!state.value.permissionGranted) return;
    if (state.value.announcedIds.has(item.id)) return;

    // Mark as announced
    state.value.announcedIds.add(item.id);
    saveAnnouncedIds();

    const spelledPartNumber = spellPartNumber(item.part.part_number);

    if (item.is_urgent) {
        const message = `Perhatian. Permintaan urgent untuk part ${spelledPartNumber}`;
        await speak(message, { urgent: true });
    } else {
        const message = `Part baru diminta ${spelledPartNumber}`;
        await speak(message, { urgent: false });
    }
}

/**
 * Send reminder for an item (throttled)
 */
async function remindItem(item: RequestItem): Promise<void> {
    if (!state.value.permissionGranted) return;

    const now = Date.now();
    const lastReminder = state.value.lastReminderTime.get(item.id) || 0;

    // Throttle reminders to once per 5 minutes
    if (now - lastReminder < 5 * 60 * 1000) return;

    state.value.lastReminderTime.set(item.id, now);

    const spelledPartNumber = spellPartNumber(item.part.part_number);

    if (item.is_urgent) {
        const message = `Pengingat. Part urgent ${spelledPartNumber} masih menunggu`;
        await speak(message, { urgent: true });
    } else {
        const message = `Pengingat. Part ${spelledPartNumber} masih menunggu`;
        await speak(message, { urgent: false });
    }
}

/**
 * Clear all announcement history
 */
function clearHistory(): void {
    state.value.announcedIds.clear();
    state.value.lastReminderTime.clear();
    localStorage.removeItem(STORAGE_KEY);
}

/**
 * Stop current speech
 */
function stopSpeaking(): void {
    window.speechSynthesis.cancel();
    currentSpeech = null;
}

export function useRequestVoice() {
    return {
        state,
        initialize,
        requestPermission,
        announceItem,
        remindItem,
        clearHistory,
        stopSpeaking,
    };
}
