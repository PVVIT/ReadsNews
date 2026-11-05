/**
 * TTS Service — dùng Microsoft Edge Neural TTS qua Node.js server (port 3001)
 * Thay thế Web Speech API để đọc tiếng Anh xen kẽ tiếng Việt chính xác hơn.
 */

const TTS_BASE_URL = import.meta.env.VITE_TTS_URL || 'http://localhost:3001'

export const ttsService = {
  /**
   * Tổng hợp giọng nói từ text, trả về Object URL audio MP3
   * @param {string} text - Văn bản cần đọc
   * @param {object} options
   * @param {string} [options.voice='vi-VN-HoaiMyNeural'] - Giọng đọc
   * @param {number} [options.rate=1] - Tốc độ đọc (0.5 - 2.0)
   * @returns {Promise<string>} objectURL để gán vào new Audio(url)
   */
  async synthesize(text, { voice = 'vi-VN-HoaiMyNeural', rate = 1 } = {}) {
    const res = await fetch(`${TTS_BASE_URL}/api/tts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        text: text.slice(0, 10000),
        voice,
        rate,
      }),
    })
    if (!res.ok) {
      const errText = await res.text().catch(() => '')
      throw new Error(`TTS Error ${res.status}: ${errText}`)
    }
    const blob = await res.blob()
    return URL.createObjectURL(blob)
  },

  /**
   * Lấy danh sách giọng đọc tiếng Việt có sẵn
   * @returns {Promise<Array>}
   */
  async getVoices() {
    const res = await fetch(`${TTS_BASE_URL}/api/tts/voices`)
    if (!res.ok) return []
    const data = await res.json()
    return data.data || []
  },

  /**
   * Kiểm tra TTS server còn sống không
   * @returns {Promise<boolean>}
   */
  async isAvailable() {
    try {
      const res = await fetch(`${TTS_BASE_URL}/api/tts/health`, { signal: AbortSignal.timeout(3000) })
      return res.ok
    } catch {
      return false
    }
  },
}

