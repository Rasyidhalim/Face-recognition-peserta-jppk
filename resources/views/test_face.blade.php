<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <title>Perekaman Wajah (File: test_face)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8f9fa; }
        #cam-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            height: 480px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin: 0 auto;
            border: 4px solid #333;
        }
        video { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; 
            transform: scaleX(-1); /* Cermin */
        }
        canvas { position: absolute; top: 0; left: 0; transform: scaleX(-1); }
        
        .status-box { 
            font-weight: bold; margin-top: 15px; padding: 10px; border-radius: 5px; 
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0">🎥 Perekaman Wajah (Test Face)</h4>
                </div>
                <div class="card-body text-center">
                    
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold">Pilih Peserta:</label>
                        <select id="peserta_id" class="form-select">
                            <option value="">-- Pilih Salah Satu --</option>
                            @foreach($pesertas as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_peserta }} ({{ $p->no_jppk }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="cam-container">
                        <video id="video" autoplay muted playsinline></video>
                    </div>

                    <div id="status" class="status-box bg-warning text-dark">
                        ⏳ Memuat Model AI...
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button id="btn-save" class="btn btn-primary btn-lg" disabled>
                            💾 Simpan Wajah
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    const video = document.getElementById('video');
    const statusDiv = document.getElementById('status');
    const btnSave = document.getElementById('btn-save');
    const selectPeserta = document.getElementById('peserta_id');
    let currentDescriptor = null;

    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Load Model
    const MODEL_PATH = '/models'; 
    Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_PATH),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_PATH),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_PATH)
    ]).then(startCamera).catch(err => {
        statusDiv.className = "status-box bg-danger text-white";
        statusDiv.innerHTML = "❌ Gagal Load Model. Cek folder public/models.";
    });

    function startCamera() {
        statusDiv.innerText = "Kamera Menyala...";
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => { video.srcObject = stream; })
            .catch(err => {
                statusDiv.innerText = "Gagal akses kamera: " + err;
            });
    }

    video.addEventListener('play', () => {
        const canvas = faceapi.createCanvasFromMedia(video);
        document.getElementById('cam-container').append(canvas);
        const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
        faceapi.matchDimensions(canvas, displaySize);

        statusDiv.className = "status-box bg-info text-dark";
        statusDiv.innerText = "Silakan hadapkan wajah...";

        setInterval(async () => {
            const detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            const resizedDetections = faceapi.resizeResults(detections, displaySize);
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            faceapi.draw.drawDetections(canvas, resizedDetections);

            if (detections.length > 0) {
                currentDescriptor = detections[0].descriptor;
                statusDiv.className = "status-box bg-success text-white";
                statusDiv.innerText = "✅ Wajah Terdeteksi!";
                
                if(selectPeserta.value !== "") btnSave.disabled = false;
            } else {
                currentDescriptor = null;
                btnSave.disabled = true;
                statusDiv.className = "status-box bg-info text-dark";
                statusDiv.innerText = "Mencari wajah...";
            }
        }, 200);
    });

    selectPeserta.addEventListener('change', () => {
        if(currentDescriptor && selectPeserta.value !== "") btnSave.disabled = false;
        else btnSave.disabled = true;
    });

    btnSave.addEventListener('click', () => {
        if (!selectPeserta.value || !currentDescriptor) return;
        
        btnSave.innerText = "Menyimpan...";
        btnSave.disabled = true;

        axios.post("{{ route('simpan.wajah') }}", {
            peserta_id: selectPeserta.value,
            face_descriptor: JSON.stringify(Object.values(currentDescriptor))
        })
        .then(res => {
            alert(res.data.message);
            selectPeserta.value = "";
            btnSave.innerText = "💾 Simpan Wajah";
        })
        .catch(err => {
            alert("Error: " + err.message);
            btnSave.disabled = false;
        });
    });
</script>

</body>
</html>