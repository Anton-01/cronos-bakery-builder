import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
    adminPanelService,
    type GatewayDriver,
    type Paginated,
    type PaymentGateway,
    type PaymentGatewayPayload,
    type Transaction,
    type TransactionFilters,
} from '../services/adminPanelService'

/**
 * Estado global del módulo de pasarelas de pago del admin: catálogo de
 * drivers soportados (dinámico, desde el backend), instancias configuradas
 * por marca y el centro de transacciones con filtros.
 */
export const usePaymentGatewayStore = defineStore('paymentGateways', () => {
    // --- Drivers y pasarelas configuradas ---
    const drivers = ref<GatewayDriver[]>([])
    const gateways = ref<PaymentGateway[]>([])
    const loadingGateways = ref(false)
    const savingGatewayId = ref<number | 'new' | null>(null)

    // --- Transacciones ---
    const transactions = ref<Paginated<Transaction> | null>(null)
    const loadingTransactions = ref(false)
    const filters = ref<TransactionFilters>({ status: '', gateway_id: null, date_from: '', date_to: '' })

    const activeGateways = computed(() => gateways.value.filter((g) => g.is_active))

    const availableDrivers = computed(() => {
        // Drivers aún sin instancia configurada (para el alta).
        const used = new Set(gateways.value.map((g) => g.driver_name))
        return drivers.value.filter((d) => !used.has(d.driver_name))
    })

    function driverFor(driverName: string): GatewayDriver | undefined {
        return drivers.value.find((d) => d.driver_name === driverName)
    }

    async function loadDrivers(): Promise<void> {
        if (drivers.value.length) return
        drivers.value = await adminPanelService.paymentDrivers()
    }

    async function loadGateways(): Promise<void> {
        loadingGateways.value = true
        try {
            gateways.value = await adminPanelService.paymentGateways()
        } finally {
            loadingGateways.value = false
        }
    }

    async function createGateway(payload: PaymentGatewayPayload): Promise<PaymentGateway> {
        savingGatewayId.value = 'new'
        try {
            const created = await adminPanelService.createPaymentGateway(payload)
            gateways.value.push(created)
            return created
        } finally {
            savingGatewayId.value = null
        }
    }

    async function updateGateway(id: number, payload: PaymentGatewayPayload): Promise<PaymentGateway> {
        savingGatewayId.value = id
        try {
            const updated = await adminPanelService.updatePaymentGateway(id, payload)
            const idx = gateways.value.findIndex((g) => g.id === id)
            if (idx !== -1) gateways.value[idx] = updated
            return updated
        } finally {
            savingGatewayId.value = null
        }
    }

    async function toggleActive(gateway: PaymentGateway): Promise<PaymentGateway> {
        return updateGateway(gateway.id, { is_active: !gateway.is_active })
    }

    async function toggleEnvironment(gateway: PaymentGateway): Promise<PaymentGateway> {
        return updateGateway(gateway.id, {
            environment: gateway.environment === 'production' ? 'sandbox' : 'production',
        })
    }

    async function removeGateway(id: number): Promise<void> {
        await adminPanelService.deletePaymentGateway(id)
        gateways.value = gateways.value.filter((g) => g.id !== id)
    }

    async function loadTransactions(page = 1): Promise<void> {
        loadingTransactions.value = true
        try {
            transactions.value = await adminPanelService.transactions({ ...filters.value, page })
        } finally {
            loadingTransactions.value = false
        }
    }

    function patchTransaction(updated: Transaction): void {
        if (!transactions.value) return
        const idx = transactions.value.data.findIndex((t) => t.id === updated.id)
        if (idx !== -1) transactions.value.data[idx] = updated
    }

    async function refundTransaction(id: number): Promise<Transaction> {
        const updated = await adminPanelService.refundTransaction(id)
        patchTransaction(updated)
        return updated
    }

    async function retryTransaction(id: number): Promise<void> {
        await adminPanelService.retryTransaction(id)
    }

    return {
        drivers,
        gateways,
        loadingGateways,
        savingGatewayId,
        transactions,
        loadingTransactions,
        filters,
        activeGateways,
        availableDrivers,
        driverFor,
        loadDrivers,
        loadGateways,
        createGateway,
        updateGateway,
        toggleActive,
        toggleEnvironment,
        removeGateway,
        loadTransactions,
        refundTransaction,
        retryTransaction,
    }
})
