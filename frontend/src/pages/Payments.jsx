import { CheckCircle2, Plus, X } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import api from '../lib/api.js'
import { localDateInput, localMonthInput } from '../lib/date.js'

const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0)
const today = localDateInput()
const currentMonth = localMonthInput()
const initialForm = { tanggal_bayar: today, metode_pembayaran: 'tunai', penghuni_id: '', catatan: '' }
const firstError = (error, fallback) => Object.values(error.response?.data?.errors || {}).flat()[0] || fallback

export default function Payments() {
  const [items, setItems] = useState([])
  const [houses, setHouses] = useState([])
  const [bills, setBills] = useState([])
  const [payers, setPayers] = useState([])
  const [selected, setSelected] = useState([])
  const [amounts, setAmounts] = useState({})
  const [houseId, setHouseId] = useState('')
  const [form, setForm] = useState(initialForm)
  const [modal, setModal] = useState(false)
  const [loadingBills, setLoadingBills] = useState(false)
  const [preparing, setPreparing] = useState(false)
  const [billingPeriod, setBillingPeriod] = useState(currentMonth)
  const [billingDuration, setBillingDuration] = useState('1')
  const [prepareNotice, setPrepareNotice] = useState('')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [modalError, setModalError] = useState('')
  const [notice, setNotice] = useState('')

  async function load() {
    try {
      const [payments, houseResponse] = await Promise.all([api.get('/pembayaran?per_page=100'), api.get('/rumah?per_page=100')])
      setItems(payments.data.data)
      setHouses(houseResponse.data.data)
    } catch { setError('Data pembayaran gagal dimuat.') }
  }

  useEffect(() => { load() }, [])

  const availablePayers = useMemo(() => payers.filter((payer) => payer.penghuni_aktif || payer.tagihan_ids.some((id) => selected.includes(id))), [payers, selected])
  const total = selected.reduce((sum, id) => sum + (Number(amounts[id]) || 0), 0)

  function openModal() {
    setHouseId(''); setBills([]); setPayers([]); setSelected([]); setAmounts({}); setForm(initialForm); setBillingPeriod(currentMonth); setBillingDuration('1'); setPrepareNotice(''); setModalError(''); setModal(true)
  }

  async function selectHouse(value) {
    setHouseId(value); setBills([]); setPayers([]); setSelected([]); setAmounts({}); setForm((current) => ({ ...current, penghuni_id: '' })); setPrepareNotice(''); setModalError('')
    if (!value) return
    setLoadingBills(true)
    try {
      const response = await api.get('/pembayaran/opsi', { params: { rumah_id: value } })
      const nextBills = response.data.data.tagihan
      setBills(nextBills)
      setPayers(response.data.data.pembayar)
      setAmounts(Object.fromEntries(nextBills.map((bill) => [bill.id, String(bill.sisa_tagihan)])))
    } catch (requestError) { setModalError(firstError(requestError, 'Tagihan rumah gagal dimuat.')) }
    finally { setLoadingBills(false) }
  }

  async function prepareBills() {
    if (!houseId) { setModalError('Pilih rumah terlebih dahulu.'); return }
    setPreparing(true); setModalError(''); setPrepareNotice('')
    try {
      const prepared = await api.post('/pembayaran/siapkan-tagihan', {
        rumah_id: Number(houseId), periode_awal: billingPeriod, durasi: Number(billingDuration),
      })
      const response = await api.get('/pembayaran/opsi', { params: { rumah_id: houseId } })
      const nextBills = response.data.data.tagihan
      const nextPayers = response.data.data.pembayar
      const preparedIds = new Set(prepared.data.data.tagihan_ids)
      setBills(nextBills)
      setPayers(nextPayers)
      setAmounts(Object.fromEntries(nextBills.map((bill) => [bill.id, String(bill.sisa_tagihan)])))
      setSelected(nextBills.filter((bill) => preparedIds.has(bill.id)).map((bill) => bill.id))
      setForm((current) => ({ ...current, penghuni_id: String(nextPayers.find((payer) => payer.penghuni_aktif)?.id || '') }))
      setPrepareNotice(prepared.data.message)
    } catch (requestError) { setModalError(firstError(requestError, 'Tagihan gagal disiapkan.')) }
    finally { setPreparing(false) }
  }

  function toggle(id) {
    setSelected((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id])
  }

  async function submit(event) {
    event.preventDefault(); setModalError('')
    const invalid = selected.find((id) => { const bill = bills.find((item) => item.id === id); const amount = Number(amounts[id]); return !amount || amount <= 0 || amount > bill.sisa_tagihan })
    if (invalid) { setModalError('Nominal setiap tagihan harus lebih dari nol dan tidak boleh melebihi sisanya.'); return }
    setSaving(true)
    try {
      await api.post('/pembayaran', {
        rumah_id: Number(houseId), penghuni_id: Number(form.penghuni_id), tanggal_bayar: form.tanggal_bayar,
        metode_pembayaran: form.metode_pembayaran, catatan: form.catatan || null,
        alokasi: selected.map((id) => ({ tagihan_id: id, nominal: Number(amounts[id]) })),
      })
      setModal(false); setNotice(`Pembayaran ${rupiah(total)} berhasil dicatat.`); await load()
    } catch (requestError) { setModalError(firstError(requestError, 'Pembayaran gagal dicatat.')) }
    finally { setSaving(false) }
  }

  return <div className="page-stack">
    <section className="page-heading"><div><span className="eyebrow">TRANSAKSI MASUK</span><h2>Riwayat Pembayaran</h2><p>Catat pembayaran penuh maupun sebagian untuk satu atau beberapa tagihan.</p></div><button className="button primary" onClick={openModal}><Plus size={18}/>Catat Pembayaran</button></section>
    {notice && <div className="alert success">{notice}</div>}{error && <div className="alert error">{error}</div>}
    <section className="panel"><div className="table-wrap"><table><thead><tr><th>Nomor Bukti</th><th>Tanggal</th><th>Rumah</th><th>Pembayar</th><th>Metode</th><th>Total</th></tr></thead><tbody>{items.map((item) => <tr key={item.id}><td><strong>{item.nomor_bukti}</strong></td><td>{new Date(item.tanggal_bayar).toLocaleDateString('id-ID')}</td><td>{item.nomor_rumah}</td><td>{item.nama_pembayar}</td><td><span className="badge neutral">{item.metode_pembayaran}</span></td><td><strong>{rupiah(item.total_bayar)}</strong></td></tr>)}{!items.length && <tr><td colSpan="6" className="empty">Belum ada pembayaran.</td></tr>}</tbody></table></div></section>
    {modal && <div className="modal-layer"><div className="modal modal-large"><div className="modal-head"><div><span className="eyebrow">TRANSAKSI BARU</span><h3>Catat Pembayaran</h3></div><button onClick={() => setModal(false)}><X/></button></div>{modalError && <div className="alert error modal-alert">{modalError}</div>}<form onSubmit={submit} className="form-grid">
      <label className="full">Rumah<select value={houseId} onChange={(event) => selectHouse(event.target.value)} required><option value="">Pilih rumah...</option>{houses.map((house) => <option value={house.id} key={house.id}>{house.nomor_rumah} — {house.penghuni_aktif?.penghuni?.nama_lengkap || 'Tanpa penghuni aktif'}</option>)}</select></label>
      <label>Periode awal<input type="month" value={billingPeriod} onChange={(event) => setBillingPeriod(event.target.value)} required/></label>
      <label>Durasi pembayaran<select value={billingDuration} onChange={(event) => setBillingDuration(event.target.value)}><option value="1">Bulanan (1 bulan)</option><option value="12">Tahunan (12 bulan)</option></select></label>
      <div className="prepare-bills full"><button type="button" className="button secondary" onClick={prepareBills} disabled={!houseId || preparing}>{preparing?'Menyiapkan...':'Siapkan & Pilih Tagihan'}</button><small>{billingDuration==='12'?'Iuran kebersihan 12 bulan; satpam hanya bulan pertama.':'Iuran kebersihan dan satpam untuk satu bulan.'}</small></div>
      {prepareNotice&&<div className="alert success full">{prepareNotice}</div>}
      <label>Tanggal pembayaran<input type="date" value={form.tanggal_bayar} onChange={(event) => setForm({ ...form, tanggal_bayar: event.target.value })} required/></label>
      <label>Metode pembayaran<select value={form.metode_pembayaran} onChange={(event) => setForm({ ...form, metode_pembayaran: event.target.value })} required><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></label>
      <label className="full">Pembayar<select value={form.penghuni_id} onChange={(event) => setForm({ ...form, penghuni_id: event.target.value })} disabled={!houseId || !availablePayers.length} required><option value="">Pilih pembayar...</option>{availablePayers.map((payer) => <option value={payer.id} key={payer.id}>{payer.nama_lengkap}{payer.penghuni_aktif ? ' — penghuni aktif' : ' — penghuni pada tagihan'}</option>)}</select><small className="field-hint">Pembayar harus penghuni aktif atau penghuni yang tercatat pada tagihan terpilih.</small></label>
      <div className="full bill-picker">{loadingBills && <div className="loading">Memuat seluruh tagihan rumah...</div>}{houseId && !loadingBills && !bills.length && <div className="empty">Rumah ini tidak memiliki tagihan tertunggak.</div>}{bills.map((bill) => <div className={`bill-option ${selected.includes(bill.id) ? 'selected' : ''}`} key={bill.id}><button type="button" className="bill-check" onClick={() => toggle(bill.id)} aria-label={`Pilih ${bill.jenis_iuran}`}><CheckCircle2 size={19}/></button><div className="bill-copy"><strong>{bill.jenis_iuran}</strong><small>{bill.periode_tagihan} • {bill.nama_penghuni} • Sisa {rupiah(bill.sisa_tagihan)}</small></div><label>Nominal<input type="number" min="1" max={bill.sisa_tagihan} step="1" value={amounts[bill.id] || ''} onChange={(event) => setAmounts((current) => ({ ...current, [bill.id]: event.target.value }))} disabled={!selected.includes(bill.id)}/></label></div>)}</div>
      <label className="full">Catatan (opsional)<textarea value={form.catatan} onChange={(event) => setForm({ ...form, catatan: event.target.value })} placeholder="Contoh: pembayaran iuran tiga bulan"/></label>
      <div className="payment-total full"><span>Total pembayaran</span><strong>{rupiah(total)}</strong></div><div className="modal-actions full"><button type="button" className="button secondary" onClick={() => setModal(false)}>Batal</button><button className="button primary" disabled={saving || !selected.length || !form.penghuni_id}>{saving ? 'Menyimpan...' : 'Simpan Pembayaran'}</button></div>
    </form></div></div>}
  </div>
}
