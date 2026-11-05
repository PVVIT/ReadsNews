/**
 * ReadsNews TTS Server – Microsoft Edge Neural Voice (100% Miễn Phí)
 * -------------------------------------------------------------------------
 * Chạy: node server.js (port 3001 mặc định)
 * API:  POST /api/tts  { text, voice?, rate? }
 *       GET  /api/tts/voices
 * Trả về audio/mpeg stream trực tiếp cho trình duyệt phát.
 */
import express from 'express'
import cors from 'cors'
import { MsEdgeTTS, OUTPUT_FORMAT } from 'msedge-tts'

const PORT = process.env.TTS_PORT || 3001
const DEFAULT_VOICE = 'vi-VN-HoaiMyNeural'

const app = express()
app.use(express.json({ limit: '50kb' }))
app.use(cors({
  origin: process.env.FRONTEND_ORIGIN || '*',
  methods: ['GET', 'POST'],
}))

// Chunk size lớn hơn — ít đoạn hơn → ít kết nối hơn → ít bị rate-limit
const CHUNK_SIZE = 800

/**
 * Chia text thành các đoạn theo ranh giới câu, tối đa CHUNK_SIZE ký tự
 */
function splitIntoChunks(text, maxLen = CHUNK_SIZE) {
  const sentences = text.match(/[^.!?\n]+[.!?\n]*/g) || [text]
  const result = []
  let current = ''

  for (const sentence of sentences) {
    if (sentence.length > maxLen) {
      // Câu quá dài → tách theo dấu phẩy
      const parts = sentence.match(/[^,]+,*/g) || [sentence]
      for (const part of parts) {
        if ((current + part).length > maxLen && current.length > 0) {
          result.push(current.trim())
          current = part
        } else {
          current += part
        }
      }
    } else if ((current + sentence).length > maxLen && current.length > 0) {
      result.push(current.trim())
      current = sentence
    } else {
      current += sentence
    }
  }

  if (current.trim()) result.push(current.trim())
  return result.filter(c => c.length > 0)
}

/**
 * Tổng hợp 1 đoạn văn → Buffer MP3
 * Dùng lại tts instance đã tạo sẵn (tránh tạo WebSocket mới liên tục)
 */
async function synthesizeWithInstance(tts, text, safeRate, maxRetry = 4) {
  let lastError = null
  for (let attempt = 1; attempt <= maxRetry; attempt++) {
    try {
      const { audioStream } = await tts.toStream(text, {
        rate: safeRate,
        pitch: '+0Hz',
        volume: 100,
      })

      const chunks = []
      let hasData = false
      await new Promise((resolve, reject) => {
        audioStream.on('data', c => { hasData = true; chunks.push(c) })
        audioStream.on('end', () => {
          if (!hasData) reject(new Error('Empty audio stream (0 bytes)'))
          else resolve()
        })
        audioStream.on('error', reject)
      })

      const buf = Buffer.concat(chunks)
      if (buf.length < 100) throw new Error(`Buffer quá nhỏ: ${buf.length}`)
      return buf
    } catch (err) {
      lastError = err
      console.warn(`[TTS] ⚠️  Chunk retry ${attempt}/${maxRetry}: ${err.message}`)
      if (attempt < maxRetry) {
        // Delay tăng dần: 1.5s, 3s, 5s
        const delay = attempt === 1 ? 1500 : attempt === 2 ? 3000 : 5000
        await new Promise(r => setTimeout(r, delay))
      }
    }
  }
  throw lastError
}

/**
 * POST /api/tts
 * Body JSON: { text: string, voice?: string, rate?: number }
 * Response: audio/mpeg
 */
app.post('/api/tts', async (req, res) => {
  const { text, voice = DEFAULT_VOICE, rate = 1 } = req.body ?? {}

  if (!text || typeof text !== 'string' || text.trim().length === 0) {
    return res.status(400).json({ success: false, message: 'Thiếu tham số text.' })
  }

  if (text.length > 10000) {
    return res.status(400).json({ success: false, message: 'Văn bản quá dài (tối đa 10.000 ký tự).' })
  }

  const cleanText = text.trim()
  const safeRate = Math.min(Math.max(parseFloat(rate) || 1, 0.5), 2)

  let tts = null
  try {
    // Dùng 1 instance TTS duy nhất cho toàn bộ request
    // → tái sử dụng WebSocket, tránh Microsoft rate-limit do tạo nhiều connection
    tts = new MsEdgeTTS()
    await tts.setMetadata(voice, OUTPUT_FORMAT.AUDIO_24KHZ_48KBITRATE_MONO_MP3)

    const chunks = splitIntoChunks(cleanText)
    console.log(`[TTS] Chia thành ${chunks.length} đoạn, voice=${voice}`)

    const audioBuffers = []
    for (let i = 0; i < chunks.length; i++) {
      const buf = await synthesizeWithInstance(tts, chunks[i], safeRate)
      audioBuffers.push(buf)
      console.log(`[TTS] ✅ Đoạn ${i + 1}/${chunks.length}: ${buf.length} bytes`)

      // Nghỉ 500ms giữa các đoạn để tránh rate-limit
      if (i < chunks.length - 1) {
        await new Promise(r => setTimeout(r, 500))
      }
    }

    const finalBuffer = Buffer.concat(audioBuffers)
    tts?.close()

    res.setHeader('Content-Type', 'audio/mpeg')
    res.setHeader('Content-Length', finalBuffer.length)
    res.setHeader('Cache-Control', 'no-store')
    res.setHeader('X-TTS-Voice', voice)
    res.end(finalBuffer)

    console.log(`[TTS] 🎵 Hoàn thành: ${finalBuffer.length} bytes tổng`)
  } catch (err) {
    tts?.close()
    console.error('[TTS] ❌ Lỗi:', err.message)
    if (!res.headersSent) {
      res.status(503).json({ success: false, message: 'Dịch vụ giọng đọc tạm thời không khả dụng.' })
    }
  }
})

/**
 * GET /api/tts/voices
 * Trả về danh sách giọng đọc tiếng Việt được hỗ trợ
 */
app.get('/api/tts/voices', (req, res) => {
  res.json({
    success: true,
    data: [
      { id: 'vi-VN-HoaiMyNeural',  name: 'HoaiMy (Nữ, Miền Nam)', gender: 'Female', locale: 'vi-VN' },
      { id: 'vi-VN-NamMinhNeural', name: 'NamMinh (Nam, Miền Nam)', gender: 'Male',   locale: 'vi-VN' },
    ],
  })
})

/** GET /api/tts/health - kiểm tra server đang chạy */
app.get('/api/tts/health', (_req, res) => {
  res.json({ success: true, status: 'ok', port: PORT })
})

app.listen(PORT, () => {
  console.log(`[TTS Server] 🎙️  Đang chạy tại http://localhost:${PORT}`)
  console.log(`[TTS Server] Voice mặc định: ${DEFAULT_VOICE}`)
  console.log(`[TTS Server] API: POST http://localhost:${PORT}/api/tts`)
})

