import { CalendarPlus, RefreshCw } from 'lucide-react'
import { useCallback, useEffect, useState } from 'react'
import api from '../lib/api.js'
import { localMonthInput } from '../lib/date.js'

const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
const currentMonth = localMonthInput()

export default function Bills() {
  const [period, setPeriod] = useState(currentMonth); const [status, setStatus] = useState(''); const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true); const [generating, setGenerating] = useState(false); const [notice, setNotice] = useState(''); const [error, setError] = useState('')
  const load = useCallback(() => { setLoading(true); api.get(`/tagihan?periode=${period}&status=${status}&per_page=100`).then(({ data }) => setItems(data.data)).catch(() => setError('Tagihan gagal dimuat.')).finally(() => setLoading(false)) }, [period, status])
  useEffect(() => { load() }, [load])
  async function generate() { setGenerating(true); setError(''); setNotice(''); try { const { data } = await api.post('/tagihan/buat-bulanan', { periode: period }); setNotice(`${data.dibuat} tagihan dibuat, ${data.dilewati_karena_sudah_ada} tagihan sudah tersedia.`); load() } catch (err) { setError(err.response?.data?.message || 'Tagihan gagal dibuat.') } finally { setGenerating(false) } }
  return <div className="page-stack"><section className="page-heading"><div><span className="eyebrow">IURAN BULANAN</span><h2>Daftar Tagihan</h2><p>Buat dan pantau tagihan satpam serta kebersihan.</p></div><button className="button primary" onClick={generate} disabled={generating}><CalendarPlus size={18}/>{generating?'Membuat...':'Buat Tagihan Bulan Ini'}</button></section>{notice&&<div className="alert success">{notice}</div>}{error&&<div className="alert error">{error}</div>}<section className="panel"><div className="filter-row"><label>Periode<input type="month" value={period} onChange={(e)=>setPeriod(e.target.value)}/></label><label>Status<select value={status} onChange={(e)=>setStatus(e.target.value)}><option value="">Semua status</option><option value="belum_lunas">Belum lunas</option><option value="sebagian">Dibayar sebagian</option><option value="lunas">Lunas</option></select></label><button className="icon-button" onClick={load} title="Muat ulang"><RefreshCw size={18}/></button></div><div className="table-wrap"><table><thead><tr><th>Rumah</th><th>Penghuni</th><th>Jenis Iuran</th><th>Nominal</th><th>Terbayar</th><th>Status</th></tr></thead><tbody>{!loading&&items.map(item=><tr key={item.id}><td><strong>{item.nomor_rumah}</strong></td><td>{item.nama_penghuni}</td><td>{item.jenis_iuran}</td><td>{rupiah(item.nominal)}</td><td>{rupiah(item.nominal_terbayar)}</td><td><span className={`badge ${item.status}`}>{item.status.replace('_',' ')}</span></td></tr>)}{!loading&&!items.length&&<tr><td colSpan="6" className="empty">Belum ada tagihan pada periode ini.</td></tr>}</tbody></table>{loading&&<div className="loading">Memuat tagihan...</div>}</div></section></div>
}
