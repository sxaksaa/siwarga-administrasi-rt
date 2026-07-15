import { Navigate, Route, Routes } from 'react-router-dom'
import { lazy, Suspense } from 'react'
import Layout from './components/Layout.jsx'

const Dashboard = lazy(() => import('./pages/Dashboard.jsx'))
const Bills = lazy(() => import('./pages/Bills.jsx'))
const Expenses = lazy(() => import('./pages/Expenses.jsx'))
const Houses = lazy(() => import('./pages/Houses.jsx'))
const Login = lazy(() => import('./pages/Login.jsx'))
const Payments = lazy(() => import('./pages/Payments.jsx'))
const Reports = lazy(() => import('./pages/Reports.jsx'))
const Residents = lazy(() => import('./pages/Residents.jsx'))

function ProtectedRoute({ children }) {
  return localStorage.getItem('token_administrasi_rt') ? children : <Navigate to="/login" replace />
}

export default function App() {
  return (
    <Suspense fallback={<div className="route-loading">Memuat halaman...</div>}><Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/" element={<ProtectedRoute><Layout /></ProtectedRoute>}>
        <Route index element={<Dashboard />} />
        <Route path="penghuni" element={<Residents />} />
        <Route path="rumah" element={<Houses />} />
        <Route path="tagihan" element={<Bills />} />
        <Route path="pembayaran" element={<Payments />} />
        <Route path="pengeluaran" element={<Expenses />} />
        <Route path="laporan" element={<Reports />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes></Suspense>
  )
}
