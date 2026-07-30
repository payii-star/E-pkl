<template>
    <div class="modal fade" ref="scannerModalRef" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Scan Barcode</h5>
            <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p class="text-muted">Arahkan barcode ke kamera. Pemindaian akan terjadi secara otomatis.</p>
            <video ref="videoRef" class="w-100 rounded"></video>
        </div>
        </div>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import { BrowserMultiFormatReader, type IScannerControls } from '@zxing/browser';
import { Modal } from 'bootstrap';

const props = defineProps<{ modelValue: boolean }>();
const emit = defineEmits(['update:modelValue', 'scan-success']);

const scannerModalRef = ref<HTMLElement | null>(null);
const videoRef = ref<HTMLVideoElement | null>(null);
let bsModal: Modal | null = null;
let controls: IScannerControls | null = null;
let codeReader: BrowserMultiFormatReader | null = null;

watch(() => props.modelValue, (isVisible) => {
    if (!scannerModalRef.value) return;
    
    if (!bsModal) {
    bsModal = new Modal(scannerModalRef.value);
    }
    
    if (isVisible) {
    bsModal.show();
    setTimeout(() => startScan(), 300); 
    } else {
    stopScan();
    bsModal.hide();
    }
});

const startScan = async () => {
    if (!videoRef.value) return;
    try {
    codeReader = new BrowserMultiFormatReader();
    
    controls = await codeReader.decodeFromVideoDevice(undefined, videoRef.value, (result, err) => {
        if (result) {
        emit('scan-success', result.getText());
        closeModal();
        }
    });
    } catch (err) {
    console.error('Error starting scanner:', err);
    closeModal();
    }
};

const stopScan = () => {
    if (controls) {
    controls.stop();
    controls = null;
    }
    if (codeReader) {
    // DIHAPUS: Baris ini menyebabkan error karena method .reset() tidak ada
    // codeReader.reset(); 
    codeReader = null;
    }
};

const closeModal = () => {
    stopScan();
    emit('update:modelValue', false);
};
    
onUnmounted(() => {
    stopScan();
});
</script>