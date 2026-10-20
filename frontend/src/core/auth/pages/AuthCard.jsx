import { Card, Typography } from 'antd';

export default function AuthCard({ title, children }) {
  return (
    <div className="auth-page">
      <Card className="auth-card">
        <Typography.Title level={3} className="auth-card-title">
          {title}
        </Typography.Title>
        {children}
      </Card>
    </div>
  );
}
