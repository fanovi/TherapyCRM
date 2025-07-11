import React, { useEffect, useState } from "react";
import { X, CheckCircle, AlertCircle, Info } from "lucide-react";

export type ToastType = "success" | "error" | "info";

export interface ToastMessage {
  id: string;
  type: ToastType;
  title: string;
  message?: string;
  duration?: number;
}

interface ToastProps {
  message: ToastMessage;
  onClose: (id: string) => void;
}

const Toast: React.FC<ToastProps> = ({ message, onClose }) => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    // Animazione di entrata
    setIsVisible(true);

    // Auto-close
    const timer = setTimeout(() => {
      setIsVisible(false);
      setTimeout(() => onClose(message.id), 300); // Delay per animazione
    }, message.duration || 5000);

    return () => clearTimeout(timer);
  }, [message.id, message.duration, onClose]);

  const getToastStyles = () => {
    const baseStyles =
      "flex items-start gap-3 p-4 rounded-lg shadow-lg border transition-all duration-300 max-w-md";

    if (!isVisible) return baseStyles + " opacity-0 transform translate-x-full";

    switch (message.type) {
      case "success":
        return baseStyles + " bg-green-50 border-green-200 text-green-800";
      case "error":
        return baseStyles + " bg-red-50 border-red-200 text-red-800";
      case "info":
        return baseStyles + " bg-blue-50 border-blue-200 text-blue-800";
      default:
        return baseStyles + " bg-gray-50 border-gray-200 text-gray-800";
    }
  };

  const getIcon = () => {
    const iconProps = { className: "h-5 w-5 flex-shrink-0 mt-0.5" };

    switch (message.type) {
      case "success":
        return (
          <CheckCircle
            {...iconProps}
            className={iconProps.className + " text-green-500"}
          />
        );
      case "error":
        return (
          <AlertCircle
            {...iconProps}
            className={iconProps.className + " text-red-500"}
          />
        );
      case "info":
        return (
          <Info
            {...iconProps}
            className={iconProps.className + " text-blue-500"}
          />
        );
      default:
        return (
          <Info
            {...iconProps}
            className={iconProps.className + " text-gray-500"}
          />
        );
    }
  };

  return (
    <div className={getToastStyles()}>
      {getIcon()}
      <div className="flex-1">
        <div className="font-semibold text-sm">{message.title}</div>
        {message.message && (
          <div className="text-sm mt-1 opacity-90">{message.message}</div>
        )}
      </div>
      <button
        onClick={() => {
          setIsVisible(false);
          setTimeout(() => onClose(message.id), 300);
        }}
        className="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
      >
        <X className="h-4 w-4" />
      </button>
    </div>
  );
};

// Container per i toast
interface ToastContainerProps {
  messages: ToastMessage[];
  onClose: (id: string) => void;
}

export const ToastContainer: React.FC<ToastContainerProps> = ({
  messages,
  onClose,
}) => {
  if (messages.length === 0) return null;

  return (
    <div className="fixed top-4 right-4 z-[9999] space-y-2">
      {messages.map((message) => (
        <Toast key={message.id} message={message} onClose={onClose} />
      ))}
    </div>
  );
};

// Hook per gestire i toast
export const useToast = () => {
  const [messages, setMessages] = useState<ToastMessage[]>([]);

  const showToast = (
    type: ToastType,
    title: string,
    message?: string,
    duration?: number
  ) => {
    const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
    const newMessage: ToastMessage = {
      id,
      type,
      title,
      message,
      duration,
    };

    setMessages((prev) => [...prev, newMessage]);
  };

  const showSuccess = (title: string, message?: string) =>
    showToast("success", title, message);
  const showError = (title: string, message?: string) =>
    showToast("error", title, message);
  const showInfo = (title: string, message?: string) =>
    showToast("info", title, message);

  const closeToast = (id: string) => {
    setMessages((prev) => prev.filter((msg) => msg.id !== id));
  };

  return {
    messages,
    showSuccess,
    showError,
    showInfo,
    closeToast,
  };
};
