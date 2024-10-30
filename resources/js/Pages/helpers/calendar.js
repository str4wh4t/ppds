import moment from "moment";

const generateDays = (year, month) => {
  const days = [];
  const startDate = new Date(year, month, 1); // Tanggal pertama di bulan
  const endDate = new Date(year, month + 1, 0); // Tanggal terakhir di bulan

  // Tentukan hari dalam seminggu di mana bulan ini dimulai
  const startDayOfWeek = (startDate.getDay() + 6) % 7; // Konversi agar Senin = 0
  const endDayOfWeek = (endDate.getDay() + 6) % 7;

  // Tambahkan tanggal dari bulan sebelumnya untuk mengisi slot kosong di awal
  for (let i = startDayOfWeek - 1; i >= 0; i--) {
    const prevDate = new Date(year, month, -i);
    days.push({
      // date: prevDate.toISOString().split('T')[0],
      date: moment(prevDate.toLocaleDateString("id-ID"), "DD/MM/YYYY").format(
        "YYYY-MM-DD"
      ),
      isCurrentMonth: false,
      events: [],
      workLoad: 0,
    });
  }

  // Loop melalui setiap hari dalam bulan yang diminta
  for (
    let date = startDate;
    date <= endDate;
    date.setDate(date.getDate() + 1)
  ) {
    const dayObj = {
      // date: date.toISOString().split('T')[0],
      // date: date.toLocaleDateString("id-ID"),
      date: moment(date.toLocaleDateString("id-ID"), "DD/MM/YYYY").format(
        "YYYY-MM-DD"
      ),
      isCurrentMonth: true,
      events: [],
      isWarning: false,
      isDanger: false,
      isToday: false,
      workLoad: 0,
    };

    // Tentukan apakah tanggal adalah hari ini
    const today = new Date();
    if (
      date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear()
    ) {
      dayObj.isToday = true;
      dayObj.events = [
        {
          id: 1,
          name: "Bimbingan",
          start_date: "2022-01-03 10:00",
          end_date: "2022-01-03 12:00",
          href: "#",
        },
        {
          id: 2,
          name: "Diskusi",
          start_date: "2022-01-03 4:00",
          end_date: "2022-01-03 05:00",
          href: "#",
        },
        {
          id: 3,
          name: "Stase : UGD",
          start_date: "2022-01-03 14:00",
          end_date: "2022-01-03 14:00",
          href: "#",
        },
        {
          id: 4,
          name: "Stase : Urologi",
          start_date: "2022-01-03 14:00",
          end_date: "2022-01-03 14:00",
          href: "#",
        },
        {
          id: 5,
          name: "Stase : Obsgyn",
          start_date: "2022-01-03 14:00",
          end_date: "2022-01-03 14:00",
          href: "#",
        },
      ];
    }

    // Contoh logika untuk menandai tanggal sebagai warning atau danger
    if (date.getDate() >= 3 && date.getDate() <= 9) {
      dayObj.isWarning = true;
    }
    if (date.getDate() >= 10 && date.getDate() <= 16) {
      dayObj.isDanger = true;
    }

    days.push(dayObj);
  }

  // Tambahkan tanggal dari bulan berikutnya untuk mengisi slot kosong di akhir
  for (let i = 1; i < 7 - endDayOfWeek; i++) {
    const nextDate = new Date(year, month + 1, i);
    days.push({
      // date: nextDate.toISOString().split('T')[0],
      // date: nextDate.toLocaleDateString("id-ID"),
      date: moment(nextDate.toLocaleDateString("id-ID"), "DD/MM/YYYY").format(
        "YYYY-MM-DD"
      ),
      isCurrentMonth: false,
      events: [],
      workLoad: 0,
    });
  }

  return days;
};

export { generateDays };
