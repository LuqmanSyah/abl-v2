window.FaceVerification = {
  initialized: false,
  modelsUrl: '/models',
  initPromise: null,

  async init() {
    if (this.initialized) return
    if (this.initPromise) return this.initPromise
    this.initPromise = Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(this.modelsUrl),
      faceapi.nets.faceLandmark68Net.loadFromUri(this.modelsUrl),
      faceapi.nets.faceRecognitionNet.loadFromUri(this.modelsUrl),
    ]).then(() => { this.initialized = true })
    return this.initPromise
  },

  async extractDescriptor(imageElement) {
    await this.init()
    const result = await faceapi
      .detectSingleFace(imageElement, new faceapi.TinyFaceDetectorOptions({ inputSize: 320 }))
      .withFaceLandmarks()
      .withFaceDescriptor()
    return result ? Array.from(result.descriptor) : null
  },

  async extractFromBlob(blob) {
    const image = new Image()
    const url = URL.createObjectURL(blob)
    image.src = url
    await image.decode()
    URL.revokeObjectURL(url)
    return this.extractDescriptor(image)
  },

  async verify(blob, previousDescriptor) {
    const image = new Image()
    const url = URL.createObjectURL(blob)
    image.src = url
    await image.decode()
    URL.revokeObjectURL(url)

    const descriptor = await this.extractDescriptor(image)
    if (!descriptor) {
      return { match: false, mismatch: false, descriptor: null, reason: 'Wajah tidak terdeteksi. Foto tetap disimpan sebagai NeedsReview.' }
    }

    if (previousDescriptor) {
      const distance = faceapi.euclideanDistance(descriptor, previousDescriptor)
      const threshold = 0.6
      return {
        match: distance < threshold,
        mismatch: distance >= threshold,
        descriptor,
        distance: +distance.toFixed(4),
        reason: distance < threshold
          ? 'Wajah cocok dengan absensi sebelumnya.'
          : `Wajah tidak cocok (jarak: ${distance.toFixed(2)}). Status diubah ke NeedsReview.`,
      }
    }

    return { match: true, mismatch: false, descriptor, reason: 'Encoding wajah disimpan sebagai referensi.' }
  },
}
