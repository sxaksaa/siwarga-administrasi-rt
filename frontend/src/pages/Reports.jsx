import { TrendingUp } from 'lucide-react'
import { useEffect, useState } from 'react'
import {
  Bar,
  ComposedChart,
  CartesianGrid,
  Legend,
  Line,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import api from '../lib/api.js'

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
}).format(value || 0)

const tanggalIndonesia = (value) => {
  if (!value) return '-'

  const date = new Date(value.includes('T') ? value : `${value}T00:00:00`)
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleDateString('id-ID')
}

const namaBulan = (value) => {
  if (!value) return '-'

  return new Date(`${value}-01T00:00:00`).toLocaleDateString('id-ID', {
    month: 'long',
    year: 'numeric',
  })
}

export default function Reports() {
  const today = new Date()
  const [year, setYear] = useState(today.getFullYear())
  const [month, setMonth] = useState(
    `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`,
  )
  const [yearlyData, setYearlyData] = useState(null)
  const [monthlyData, setMonthlyData] = useState(null)
  const [yearlyLoading, setYearlyLoading] = useState(true)
  const [monthlyLoading, setMonthlyLoading] = useState(true)
  const [yearlyError, setYearlyError] = useState('')
  const [monthlyError, setMonthlyError] = useState('')

  useEffect(() => {
    let active = true

    setYearlyLoading(true)
    setYearlyError('')
    setYearlyData(null)
    api.get(`/laporan/tahunan?tahun=${year}`)
      .then(({ data }) => {
        if (active) setYearlyData(data)
      })
      .catch(() => {
        if (active) setYearlyError('Laporan tahunan gagal dimuat.')
      })
      .finally(() => {
        if (active) setYearlyLoading(false)
      })

    return () => {
      active = false
    }
  }, [year])

  useEffect(() => {
    let active = true

    setMonthlyLoading(true)
    setMonthlyError('')
    setMonthlyData(null)
    api.get(`/laporan/bulanan?bulan=${month}`)
      .then(({ data }) => {
        if (active) setMonthlyData(data)
      })
      .catch(() => {
        if (active) setMonthlyError('Detail laporan bulanan gagal dimuat.')
      })
      .finally(() => {
        if (active) setMonthlyLoading(false)
      })

    return () => {
      active = false
    }
  }, [month])

  const chart = (yearlyData?.bulanan || []).map((item) => ({
    ...item,
    bulan: item.bulan.slice(0, 3),
  }))
  const payments = monthlyData?.pembayaran || []
  const expenses = monthlyData?.pengeluaran || []

  return (
    <div className="page-stack">
      <section className="page-heading">
        <div>
          <span className="eyebrow">REKAP KEUANGAN</span>
          <h2>Laporan Keuangan</h2>
          <p>Pantau ringkasan tahunan dan rincian transaksi setiap bulan.</p>
        </div>
        <div className="report-actions">
          <label className="standalone-label">
            Tahun laporan
            <select value={year} onChange={(event) => setYear(Number(event.target.value))}>
              {[0, 1, 2].map((offset) => {
                const optionYear = today.getFullYear() - offset
                return <option key={optionYear}>{optionYear}</option>
              })}
            </select>
          </label>
          <label className="standalone-label">
            Detail bulan
            <input
              type="month"
              value={month}
              onChange={(event) => event.target.value && setMonth(event.target.value)}
            />
          </label>
        </div>
      </section>

      {yearlyError && <div className="alert error">{yearlyError}</div>}

      <section className="stat-grid report-stats">
        <article className="stat-card">
          <div>
            <p>Total Pemasukan {year}</p>
            <strong className="text-green">{rupiah(yearlyData?.total_pemasukan)}</strong>
          </div>
        </article>
        <article className="stat-card">
          <div>
            <p>Total Pengeluaran {year}</p>
            <strong className="text-orange">{rupiah(yearlyData?.total_pengeluaran)}</strong>
          </div>
        </article>
        <article className="stat-card">
          <div>
            <p>Saldo Akhir {year}</p>
            <strong>{rupiah(yearlyData?.saldo_akhir)}</strong>
          </div>
        </article>
      </section>

      <section className="panel">
        <div className="panel-heading">
          <div>
            <span className="eyebrow">GRAFIK 12 BULAN</span>
            <h3>Pemasukan, Pengeluaran, dan Saldo {year}</h3>
          </div>
          <TrendingUp />
        </div>
        {yearlyLoading ? (
          <div className="loading">Memuat laporan tahunan...</div>
        ) : (
          <div className="chart-wrap report-chart">
            <ResponsiveContainer width="100%" height="100%">
              <ComposedChart data={chart}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="bulan" />
                <YAxis tickFormatter={(value) => `${value / 1000}k`} />
                <Tooltip formatter={(value) => rupiah(value)} />
                <Legend />
                <Bar dataKey="pemasukan" name="Pemasukan" fill="#168f72" radius={[5, 5, 0, 0]} />
                <Bar dataKey="pengeluaran" name="Pengeluaran" fill="#e68b40" radius={[5, 5, 0, 0]} />
                <Line type="monotone" dataKey="saldo" name="Saldo Sisa" stroke="#3979b4" strokeWidth={3} dot={{ r: 3 }} />
              </ComposedChart>
            </ResponsiveContainer>
          </div>
        )}
      </section>

      <section className="panel">
        <div className="panel-heading">
          <div>
            <span className="eyebrow">RINGKASAN TAHUNAN</span>
            <h3>Saldo per Bulan</h3>
          </div>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Bulan</th>
                <th>Pemasukan</th>
                <th>Pengeluaran</th>
                <th>Selisih</th>
                <th>Saldo</th>
              </tr>
            </thead>
            <tbody>
              {(yearlyData?.bulanan || []).map((item) => (
                <tr key={item.nomor_bulan}>
                  <td><strong>{item.bulan}</strong></td>
                  <td className="text-green">{rupiah(item.pemasukan)}</td>
                  <td className="text-orange">{rupiah(item.pengeluaran)}</td>
                  <td>{rupiah(item.selisih)}</td>
                  <td><strong>{rupiah(item.saldo)}</strong></td>
                </tr>
              ))}
              {!yearlyLoading && !yearlyData?.bulanan?.length && (
                <tr><td colSpan="5" className="empty">Belum ada data laporan tahunan.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </section>

      <section className="panel">
        <div className="panel-heading">
          <div>
            <span className="eyebrow">DETAIL BULANAN</span>
            <h3>Ringkasan {namaBulan(month)}</h3>
          </div>
        </div>
        {monthlyError && <div className="alert error">{monthlyError}</div>}
        {monthlyLoading ? (
          <div className="loading">Memuat detail laporan bulanan...</div>
        ) : (
          <div className="stat-grid monthly-report-stats">
            <article className="stat-card">
              <div>
                <p>Saldo Awal</p>
                <strong>{rupiah(monthlyData?.saldo_awal)}</strong>
              </div>
            </article>
            <article className="stat-card">
              <div>
                <p>Pemasukan</p>
                <strong className="text-green">{rupiah(monthlyData?.total_pemasukan)}</strong>
              </div>
            </article>
            <article className="stat-card">
              <div>
                <p>Pengeluaran</p>
                <strong className="text-orange">{rupiah(monthlyData?.total_pengeluaran)}</strong>
              </div>
            </article>
            <article className="stat-card">
              <div>
                <p>Saldo Akhir</p>
                <strong>{rupiah(monthlyData?.saldo_akhir)}</strong>
              </div>
            </article>
          </div>
        )}
      </section>

      <section className="panel">
        <div className="panel-heading">
          <div>
            <span className="eyebrow">PEMASUKAN BULANAN</span>
            <h3>Daftar Pembayaran</h3>
          </div>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Rumah</th>
                <th>Pembayar</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              {payments.map((payment) => (
                <tr key={payment.id}>
                  <td>{tanggalIndonesia(payment.tanggal_bayar)}</td>
                  <td><strong>{payment.nomor_bukti}</strong></td>
                  <td>{payment.nomor_rumah || '-'}</td>
                  <td>{payment.nama_pembayar || '-'}</td>
                  <td className="text-green"><strong>{rupiah(payment.total_bayar)}</strong></td>
                </tr>
              ))}
              {!monthlyLoading && !payments.length && (
                <tr><td colSpan="5" className="empty">Belum ada pemasukan pada bulan ini.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </section>

      <section className="panel">
        <div className="panel-heading">
          <div>
            <span className="eyebrow">PENGELUARAN BULANAN</span>
            <h3>Daftar Pengeluaran</h3>
          </div>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th>Sifat</th>
                <th>Nominal</th>
              </tr>
            </thead>
            <tbody>
              {expenses.map((expense) => (
                <tr key={expense.id}>
                  <td>{tanggalIndonesia(expense.tanggal_pengeluaran)}</td>
                  <td>{expense.kategori}</td>
                  <td><strong>{expense.keterangan}</strong></td>
                  <td>
                    <span className={`badge ${expense.rutin ? 'tetap' : 'neutral'}`}>
                      {expense.rutin ? 'Rutin' : 'Tidak rutin'}
                    </span>
                  </td>
                  <td className="text-orange"><strong>{rupiah(expense.nominal)}</strong></td>
                </tr>
              ))}
              {!monthlyLoading && !expenses.length && (
                <tr><td colSpan="5" className="empty">Belum ada pengeluaran pada bulan ini.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  )
}
