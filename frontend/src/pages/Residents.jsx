import { Eye, Pencil, Plus, Search, UserRound, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import api from '../lib/api.js'

const emptyForm = { nama_lengkap: '', nomor_telepon: '', jenis_penghuni: 'tetap', sudah_menikah: '0', foto_ktp: null }

export default function Residents() {
  const [items, setItems] = useState([])
  const [search, setSearch] = useState('')
  const [modal, setModal] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  function load(query = '') {
    setLoading(true)
    api.get(`/penghuni?cari=${encodeURIComponent(query)}&per_page=100`)
      .then(({ data }) => setItems(data.data))
      .catch(() => setError('Data penghuni gagal dimuat.'))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  function openCreate() {
    setEditing(null); setForm(emptyForm); setError(''); setModal(true)
  }

  function openEdit(item) {
    setEditing(item)
    setForm({
      nama_lengkap: item.nama_lengkap,
      nomor_telepon: item.nomor_telepon,
      jenis_penghuni: item.jenis_penghuni,
      sudah_menikah: item.sudah_menikah ? '1' : '0',
      foto_ktp: null,
    })
    setError(''); setModal(true)
  }

  async function submit(event) {
    event.preventDefault(); setSaving(true); setError('')
    const body = new FormData()
    Object.entries(form).forEach(([key, value]) => value !== null && body.append(key, value))
    if (editing) body.append('_method', 'PATCH')
    try {
      await api.post(editing ? `/penghuni/${editing.id}` : '/penghuni', body)
      setModal(false); setNotice(editing ? 'Data penghuni berhasil diperbarui.' : 'Penghuni berhasil ditambahkan.'); load(search)
    } catch (err) {
      setError(Object.values(err.response?.data?.errors || {}).flat()[0] || 'Data gagal disimpan.')
    } finally { setSaving(false) }
  }

  async function viewKtp(item) {
    setError('')
    try {
      const response = await api.get(`/penghuni/${item.id}/foto-ktp`, { responseType: 'blob' })
      const objectUrl = URL.createObjectURL(response.data)
      window.open(objectUrl, '_blank', 'noopener,noreferrer')
      window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60000)
    } catch { setError('Foto KTP tidak dapat dibuka.') }
  }

  return <div className="page-stack">
    <section className="page-heading"><div><span className="eyebrow">DATA MASTER</span><h2>Daftar Penghuni</h2><p>Kelola identitas penghuni tetap dan kontrak.</p></div><button className="button primary" onClick={openCreate}><Plus size={18}/>Tambah Penghuni</button></section>
    {notice && <div className="alert success">{notice}</div>}{error && !modal && <div className="alert error">{error}</div>}
    <section className="panel"><div className="table-toolbar"><div className="search-box"><Search size={18}/><input placeholder="Cari nama atau nomor telepon..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load(search)}/></div><button className="button secondary" onClick={() => load(search)}>Cari</button></div>
      <div className="table-wrap"><table><thead><tr><th>Penghuni</th><th>Nomor Telepon</th><th>Status</th><th>Pernikahan</th><th>Aksi</th></tr></thead><tbody>{!loading && items.map((item) => <tr key={item.id}><td><div className="person-cell"><div className="avatar"><UserRound size={17}/></div><strong>{item.nama_lengkap}</strong></div></td><td>{item.nomor_telepon}</td><td><span className={`badge ${item.jenis_penghuni}`}>{item.jenis_penghuni}</span></td><td>{item.sudah_menikah ? 'Sudah menikah' : 'Belum menikah'}</td><td><div className="row-actions">{item.foto_ktp_tersedia && <button className="table-action neutral-action" onClick={() => viewKtp(item)}><Eye size={15}/>KTP</button>}<button className="table-action" onClick={() => openEdit(item)}><Pencil size={15}/>Edit</button></div></td></tr>)}{!loading && !items.length && <tr><td colSpan="5" className="empty">Belum ada data penghuni.</td></tr>}</tbody></table>{loading && <div className="loading">Memuat data...</div>}</div>
    </section>
    {modal && <div className="modal-layer"><div className="modal"><div className="modal-head"><div><span className="eyebrow">{editing ? 'PERBARUI DATA' : 'DATA BARU'}</span><h3>{editing ? 'Edit Penghuni' : 'Tambah Penghuni'}</h3></div><button onClick={() => setModal(false)}><X/></button></div>{error && <div className="alert error modal-alert">{error}</div>}<form onSubmit={submit} className="form-grid"><label className="full">Nama lengkap<input value={form.nama_lengkap} onChange={(e) => setForm({...form, nama_lengkap:e.target.value})} required/></label><label>Nomor telepon<input type="tel" inputMode="tel" maxLength="20" value={form.nomor_telepon} onChange={(e) => setForm({...form, nomor_telepon:e.target.value})} required/><small className="field-hint">10-15 digit; boleh memakai +, spasi, tanda kurung, atau tanda hubung.</small></label><label>Jenis penghuni<select value={form.jenis_penghuni} onChange={(e) => setForm({...form, jenis_penghuni:e.target.value})}><option value="tetap">Tetap</option><option value="kontrak">Kontrak</option></select></label><label>Status pernikahan<select value={form.sudah_menikah} onChange={(e) => setForm({...form, sudah_menikah:e.target.value})}><option value="0">Belum menikah</option><option value="1">Sudah menikah</option></select></label><label>Foto KTP<input type="file" accept="image/*" onChange={(e) => setForm({...form, foto_ktp:e.target.files[0] || null})} required={!editing}/><small className="field-hint">{editing ? 'Kosongkan jika foto tidak berubah.' : 'JPG, PNG, atau WebP maksimal 5 MB.'}</small></label><div className="modal-actions full"><button type="button" className="button secondary" onClick={() => setModal(false)}>Batal</button><button className="button primary" disabled={saving}>{saving ? 'Menyimpan...' : 'Simpan Penghuni'}</button></div></form></div></div>}
  </div>
}
