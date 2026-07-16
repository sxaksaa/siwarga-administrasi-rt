import { ArrowRightLeft, History, Home, MapPin, Pencil, Plus, Search, UserPlus, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import api from '../lib/api.js'
import { localDateInput } from '../lib/date.js'

const emptyHouse = { nomor_rumah: '', alamat: '', catatan: '' }
const today = localDateInput()

export default function Houses() {
  const [items, setItems] = useState([])
  const [residents, setResidents] = useState([])
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [houseModal, setHouseModal] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyHouse)
  const [detail, setDetail] = useState(null)
  const [occupancyModal, setOccupancyModal] = useState(false)
  const [occupancyForm, setOccupancyForm] = useState({ penghuni_id: '', mulai_tinggal: today, catatan: '' })
  const [endDate, setEndDate] = useState(today)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const occupiedResidentHouses = new Map(
    items.flatMap((house) => house.penghuni_aktif?.penghuni
      ? [[house.penghuni_aktif.penghuni.id, house.nomor_rumah]]
      : []),
  )
  const availableResidents = residents.filter((person) => !occupiedResidentHouses.has(person.id))
  const occupiedResidents = residents.filter((person) => occupiedResidentHouses.has(person.id))

  function load(query = '') {
    setLoading(true)
    Promise.all([api.get(`/rumah?cari=${encodeURIComponent(query)}&per_page=100`), api.get('/penghuni?per_page=100')])
      .then(([houses, people]) => { setItems(houses.data.data); setResidents(people.data.data) })
      .catch(() => setError('Data rumah gagal dimuat.'))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  function openCreate() { setEditing(null); setForm(emptyHouse); setError(''); setHouseModal(true) }
  function openEdit(item) { setEditing(item); setForm({ nomor_rumah: item.nomor_rumah, alamat: item.alamat || '', catatan: item.catatan || '' }); setError(''); setHouseModal(true) }

  async function saveHouse(event) {
    event.preventDefault(); setSaving(true); setError('')
    try {
      if (editing) await api.patch(`/rumah/${editing.id}`, form)
      else await api.post('/rumah', form)
      setHouseModal(false); setNotice(editing ? 'Data rumah berhasil diperbarui.' : 'Rumah berhasil ditambahkan.'); load(search)
    } catch (err) { setError(Object.values(err.response?.data?.errors || {}).flat()[0] || 'Data rumah gagal disimpan.') }
    finally { setSaving(false) }
  }

  async function openDetail(item) {
    setError('')
    try { const { data } = await api.get(`/rumah/${item.id}`); setDetail(data.data) }
    catch { setError('Detail rumah gagal dimuat.') }
  }

  async function assignResident(event) {
    event.preventDefault(); setSaving(true); setError('')
    try {
      await api.post(`/rumah/${detail.id}/hunian`, { ...occupancyForm, penghuni_id: Number(occupancyForm.penghuni_id) })
      setOccupancyModal(false); setNotice('Penghuni berhasil ditempatkan.'); await openDetail(detail); load(search)
    } catch (err) { setError(Object.values(err.response?.data?.errors || {}).flat()[0] || 'Penghuni gagal ditempatkan.') }
    finally { setSaving(false) }
  }

  async function endOccupancy() {
    setSaving(true); setError('')
    try {
      await api.patch(`/rumah/${detail.id}/hunian/${detail.penghuni_aktif.id}/selesai`, { selesai_tinggal: endDate })
      setNotice('Masa tinggal berhasil diakhiri.'); await openDetail(detail); load(search)
    } catch (err) { setError(Object.values(err.response?.data?.errors || {}).flat()[0] || 'Masa tinggal gagal diakhiri.') }
    finally { setSaving(false) }
  }

  return <div className="page-stack">
    <section className="page-heading"><div><span className="eyebrow">DATA MASTER</span><h2>Daftar Rumah</h2><p>Pantau status hunian dan riwayat penghuni setiap rumah.</p></div><button className="button primary" onClick={openCreate}><Plus size={18}/>Tambah Rumah</button></section>
    {notice && <div className="alert success">{notice}</div>}{error && !houseModal && !occupancyModal && <div className="alert error">{error}</div>}
    <section className="panel"><div className="table-toolbar"><div className="search-box"><Search size={18}/><input placeholder="Cari nomor rumah atau alamat..." value={search} onChange={(e)=>setSearch(e.target.value)} onKeyDown={(e)=>e.key==='Enter'&&load(search)}/></div><button className="button secondary" onClick={()=>load(search)}>Cari</button></div><div className="house-grid">{!loading&&items.map(item=><article className="house-card" key={item.id}><div className="house-card-top"><span className="house-icon"><Home/></span><span className={`badge ${item.status}`}>{item.status==='dihuni'?'Dihuni':'Tidak dihuni'}</span></div><h3>{item.nomor_rumah}</h3><p><MapPin size={15}/>{item.alamat||'Alamat belum ditambahkan'}</p><div className="house-resident"><small>PENGHUNI AKTIF</small><strong>{item.penghuni_aktif?.penghuni?.nama_lengkap||'Belum ada penghuni'}</strong></div><div className="house-actions"><button onClick={()=>openDetail(item)}><History size={15}/>Detail & riwayat</button><button onClick={()=>openEdit(item)}><Pencil size={15}/>Edit</button></div></article>)}{!loading&&!items.length&&<div className="empty">Belum ada data rumah.</div>}{loading&&<div className="loading">Memuat data...</div>}</div></section>

    {houseModal&&<div className="modal-layer"><div className="modal"><div className="modal-head"><div><span className="eyebrow">{editing?'PERBARUI DATA':'DATA BARU'}</span><h3>{editing?'Edit Rumah':'Tambah Rumah'}</h3></div><button onClick={()=>setHouseModal(false)}><X/></button></div>{error&&<div className="alert error modal-alert">{error}</div>}<form onSubmit={saveHouse} className="form-grid"><label>Nomor rumah<input value={form.nomor_rumah} onChange={(e)=>setForm({...form,nomor_rumah:e.target.value})} placeholder="Contoh: A-21" required/></label><label>Alamat<input value={form.alamat} onChange={(e)=>setForm({...form,alamat:e.target.value})}/></label><label className="full">Catatan<textarea value={form.catatan} onChange={(e)=>setForm({...form,catatan:e.target.value})}/></label><div className="modal-actions full"><button type="button" className="button secondary" onClick={()=>setHouseModal(false)}>Batal</button><button className="button primary" disabled={saving}>{saving?'Menyimpan...':'Simpan Rumah'}</button></div></form></div></div>}

    {detail&&<div className="modal-layer"><div className="modal modal-large"><div className="modal-head"><div><span className="eyebrow">DETAIL RUMAH</span><h3>{detail.nomor_rumah}</h3></div><button onClick={()=>setDetail(null)}><X/></button></div><div className="detail-summary"><div><small>STATUS</small><span className={`badge ${detail.status}`}>{detail.status==='dihuni'?'Dihuni':'Tidak dihuni'}</span></div><div><small>ALAMAT</small><strong>{detail.alamat||'-'}</strong></div><div><small>PENGHUNI AKTIF</small><strong>{detail.penghuni_aktif?.penghuni?.nama_lengkap||'Belum ada'}</strong></div></div>{detail.status==='tidak_dihuni'?<button className="button primary" onClick={()=>{setOccupancyForm({penghuni_id:'',mulai_tinggal:today,catatan:''});setError('');setOccupancyModal(true)}}><UserPlus size={17}/>Tempatkan Penghuni</button>:<div className="end-occupancy"><label>Tanggal keluar<input type="date" value={endDate} min={detail.penghuni_aktif?.mulai_tinggal} onChange={e=>setEndDate(e.target.value)}/><small className="field-hint">Penghuni baru boleh mulai pada tanggal yang sama.</small></label><button className="button warning" onClick={endOccupancy} disabled={saving}><ArrowRightLeft size={17}/>{saving?'Memproses...':'Akhiri Masa Tinggal'}</button></div>}<div className="history-section"><h4>Riwayat Penghuni</h4>{detail.riwayat_hunian?.length?detail.riwayat_hunian.map(row=><div className="history-row" key={row.id}><div className="avatar"><UserPlus size={16}/></div><div><strong>{row.penghuni?.nama_lengkap}</strong><small>{row.mulai_tinggal} — {row.selesai_tinggal||'Sekarang'}</small></div><span className={`badge ${row.aktif?'dihuni':'neutral'}`}>{row.aktif?'Aktif':'Selesai'}</span></div>):<div className="empty">Belum ada riwayat penghuni.</div>}</div></div></div>}

    {occupancyModal&&<div className="modal-layer modal-layer-top"><div className="modal"><div className="modal-head"><div><span className="eyebrow">HUNIAN BARU</span><h3>Tempatkan Penghuni</h3></div><button onClick={()=>setOccupancyModal(false)}><X/></button></div>{error&&<div className="alert error modal-alert">{error}</div>}<form className="form-grid" onSubmit={assignResident}><label className="full">Pilih penghuni<select value={occupancyForm.penghuni_id} onChange={e=>setOccupancyForm({...occupancyForm,penghuni_id:e.target.value})} required><option value="">Pilih penghuni...</option><optgroup label="Tersedia">{availableResidents.map(person=><option value={person.id} key={person.id}>{person.nama_lengkap} — {person.jenis_penghuni}</option>)}</optgroup>{occupiedResidents.length>0&&<optgroup label="Sudah menempati rumah" disabled>{occupiedResidents.map(person=><option value={person.id} key={person.id}>{person.nama_lengkap} — {occupiedResidentHouses.get(person.id)}</option>)}</optgroup>}</select><small className="field-hint">Penghuni yang masih aktif di rumah lain tidak dapat dipilih.</small></label><label>Tanggal mulai<input type="date" value={occupancyForm.mulai_tinggal} onChange={e=>setOccupancyForm({...occupancyForm,mulai_tinggal:e.target.value})} required/><small className="field-hint">Boleh sama dengan tanggal keluar penghuni sebelumnya.</small></label><label>Catatan<input value={occupancyForm.catatan} onChange={e=>setOccupancyForm({...occupancyForm,catatan:e.target.value})}/></label><div className="modal-actions full"><button type="button" className="button secondary" onClick={()=>setOccupancyModal(false)}>Batal</button><button className="button primary" disabled={saving}>{saving?'Menyimpan...':'Tempatkan Penghuni'}</button></div></form></div></div>}
  </div>
}
