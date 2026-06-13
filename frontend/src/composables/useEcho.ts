import { ref, onUnmounted } from 'vue'
import { useToast } from './useToast'

interface EchoChannel {
    listen(event: string, callback: (data: unknown) => void): EchoChannel
    stopListening(event: string): EchoChannel
}

interface EchoInstance {
    private(channel: string): EchoChannel
    channel(channel: string): EchoChannel
    leave(channel: string): void
    disconnect(): void
}

declare global {
    interface Window {
        Echo?: EchoInstance
    }
}

const echoReady = ref(false)

export function initEcho() {
    if (window.Echo) {
        echoReady.value = true
        return
    }

    try {
        const Pusher = (window as unknown as { Pusher?: unknown }).Pusher
        if (!Pusher) {
            console.warn('[Echo] Pusher not loaded — real-time features disabled')
            return
        }

        const EchoLib = (window as unknown as { Echo?: new (opts: unknown) => EchoInstance }).Echo
        if (!EchoLib) {
            console.warn('[Echo] Laravel Echo not loaded')
            return
        }

        window.Echo = new EchoLib({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            forceTLS: true,
            authEndpoint: `${import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api'}/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('admin_token') ?? ''}`,
                },
            },
        })

        echoReady.value = true
    } catch (err) {
        console.error('[Echo] init failed:', err)
    }
}

export function useEcho() {
    const subscribedChannels: string[] = []
    const { success, info } = useToast()

    function listenPrivate<T = unknown>(
        channelName: string,
        eventName: string,
        callback: (data: T) => void,
    ) {
        if (!window.Echo) {
            console.warn('[Echo] Not initialized — call initEcho() first')
            return
        }

        window.Echo.private(channelName).listen(eventName, callback as (data: unknown) => void)
        subscribedChannels.push(channelName)
    }

    function listenForJobCompleted(
        tenantId: string,
        callback: (data: { job_type: string; model_id: string; status: string; message?: string }) => void,
    ) {
        listenPrivate(
            `private-tenant.${tenantId}`,
            '.job.completed',
            callback,
        )
    }

    function listenForMediaProcessed(
        tenantId: string,
        onProcessed: (assetId: string, status: string) => void,
    ) {
        listenForJobCompleted(tenantId, (data) => {
            if (data.job_type === 'media_processing') {
                onProcessed(data.model_id, data.status)
                if (data.status === 'completed') {
                    success(`Imagen procesada correctamente`)
                } else {
                    info(`El procesamiento de imagen falló: ${data.message ?? 'error desconocido'}`)
                }
            }
        })
    }

    function listenForContentApproval(
        tenantId: string,
        onApproval: (data: { model_id: string; status: string; approved_by?: string }) => void,
    ) {
        listenPrivate(
            `private-tenant.${tenantId}`,
            '.content.status-changed',
            onApproval,
        )
    }

    function leaveAll() {
        if (!window.Echo) return
        subscribedChannels.forEach((ch) => window.Echo!.leave(ch))
        subscribedChannels.length = 0
    }

    onUnmounted(leaveAll)

    return {
        echoReady,
        listenPrivate,
        listenForJobCompleted,
        listenForMediaProcessed,
        listenForContentApproval,
        leaveAll,
    }
}