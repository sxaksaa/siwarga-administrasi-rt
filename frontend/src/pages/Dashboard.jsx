import { ArrowDownRight, ArrowUpRight, CircleDollarSign, Home, TrendingUp, Users } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import api from '../lib/api.js'

const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0)

export default function Dashboard() {
  const user = JSON.parse(localStorage.getItem('pengguna_administrasi_rt') || '{}')
  const [report, setReport] = useState(null)
  const [houses, setHouses] = useState([])
  const [error, setError] = useState('')
  const year = new Date().getFullYear()

  useEffect(() => {
    Promise.all([api.get(`/laporan/tahunan?tahun=${year}`), api.get('/rumah')])
      .then(([reportResponse, houseResponse]) => { setReport(reportResponse.data); setHouses(houseResponse.data.data) })
      .catch(() => setError('Data dashboard belum dapat dimuat.'))
  }, [year])

  const occupied = houses.filter((house) => house.status === 'dihuni').length
  const chartData = (report?.bulanan || []).map((item) => ({ ...item, bulan: item.bulan.slice(0, 3) }))
  const cards = [
    { label: 'Total Pemasukan', value: rupiah(report?.total_pemasukan), icon: ArrowUpRight, tone: 'green' },
    { label: 'Total Pengeluaran', value: rupiah(report?.total_pengeluaran), icon: ArrowDownRight, tone: 'orange' },
    { label: 'Saldo Saat Ini', value: rupiah(report?.saldo_akhir), icon: CircleDollarSign, tone: 'blue' },
    { label: 'Rumah Dihuni', value: `${occupied} dari ${houses.length || 20}`, icon: Home, tone: 'purple' },
  ]

  return <div className="page-stack">
    <section className="page-heading"><div><span className="eyebrow">RINGKASAN {year}</span><h2>Selamat datang, {user.nama || 'Admin'}</h2><p>Pantau kondisi administrasi lingkungan dalam satu tampilan.</p></div><div className="status-pill"><span /> Sistem aktif</div></section>
    {error && <div className="alert error">{error}</div>}
    <section className="stat-grid">{cards.map(({ label, value, icon: Icon, tone }) => <article className="stat-card" key={label}><div className={`stat-icon ${tone}`}><Icon /></div><div><p>{label}</p><strong>{value}</strong></div></article>)}</section>
    <section className="dashboard-grid">
      <article className="panel chart-panel"><div className="panel-heading"><div><span className="eyebrow">ARUS KAS</span><h3>Grafik pemasukan & pengeluaran</h3></div><TrendingUp size={20} /></div><div className="chart-wrap"><ResponsiveContainer width="100%" height="100%"><AreaChart data={chartData}><defs><linearGradient id="income" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#168f72" stopOpacity={0.25}/><stop offset="95%" stopColor="#168f72" stopOpacity={0}/></linearGradient></defs><CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e8ece9"/><XAxis dataKey="bulan" axisLine={false} tickLine={false}/><YAxis axisLine={false} tickLine={false} tickFormatter={(v) => `${v / 1000}k`}/><Tooltip formatter={(v) => rupiah(v)}/><Area type="monotone" dataKey="pemasukan" stroke="#168f72" strokeWidth={3} fill="url(#income)"/><Area type="monotone" dataKey="pengeluaran" stroke="#e68b40" strokeWidth={2} fill="transparent"/></AreaChart></ResponsiveContainer></div>
      </article>
      <article className="panel occupancy-panel"><div className="panel-heading"><div><span className="eyebrow">HUNIAN</span><h3>Status rumah</h3></div><Users size={20} /></div><div className="occupancy-number"><strong>{occupied}</strong><span>rumah dihuni</span></div><div className="progress"><span style={{ width: `${(occupied / (houses.length || 20)) * 100}%` }} /></div><div className="legend"><span><i className="dot green" />Dihuni <b>{occupied}</b></span><span><i className="dot gray" />Kosong <b>{(houses.length || 20) - occupied}</b></span></div></article>
    </section>
  </div>
}
